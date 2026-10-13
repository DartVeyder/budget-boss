<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Setting\BudgetShare;

use App\Models\BudgetShare;
use App\Models\FinanceBill;
use App\Models\FinanceTransactionCategory;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Password;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BudgetShareScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $user = Auth::user();
        $sharedIds = $user->getSharedUserIds();
        $isSharing = count($sharedIds) > 1;

        // Current user entry
        $members = collect([
            new Repository([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_label' => 'Ви (Поточний акаунт)',
                'bills_count' => $user->bills()->count(),
                'transactions_count' => $user->transactions()->count(),
                'linked_at' => $user->created_at?->format('d.m.Y H:i') ?? '—',
                'is_self' => true,
            ])
        ]);

        // Query all linked partners
        $shares = BudgetShare::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('partner_id', $user->id);
        })->get();

        foreach ($shares as $share) {
            $partnerId = $share->user_id === $user->id ? $share->partner_id : $share->user_id;
            $partner = User::find($partnerId);
            if ($partner) {
                $members->push(new Repository([
                    'id' => $partner->id,
                    'share_id' => $share->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'role' => $share->role ?? BudgetShare::ROLE_EDITOR,
                    'role_title' => $share->getRoleTitle(),
                    'nickname' => $share->nickname ?: 'Партнер',
                    'can_create_transactions' => $share->can_create_transactions,
                    'can_manage_bills' => $share->can_manage_bills,
                    'bills_count' => $partner->bills()->count(),
                    'transactions_count' => $partner->transactions()->count(),
                    'linked_at' => $share->created_at?->format('d.m.Y H:i') ?? '—',
                    'is_self' => false,
                ]));
            }
        }

        // Available users for linking
        $availableUsers = User::whereNotIn('id', $sharedIds)
            ->pluck('name', 'id')
            ->toArray();

        return [
            'is_sharing' => $isSharing,
            'members' => $members,
            'available_users' => $availableUsers,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Спільний бюджет (Сімейний доступ)';
    }

    /**
     * Permissions required to access this screen.
     */
    public function permission(): ?iterable
    {
        return [
            'platform.setting.budget-shares',
        ];
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Налаштування спільного доступу до рахунків та транзакцій для кількох користувачів.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Підключити існуючого користувача')
                ->icon('bs.link-45deg')
                ->modal('linkUserModal')
                ->method('linkUser'),

            ModalToggle::make('Створити нового користувача')
                ->icon('bs.person-plus')
                ->modal('createUserModal')
                ->method('createUser'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::view('setting.budget_share.info'),

            Layout::table('members', [
                TD::make('name', 'Користувач')
                    ->render(function (Repository $member) {
                        $isSelf = (bool)$member->get('is_self');
                        $color = $isSelf ? 'text-primary' : 'text-success';
                        return "<div class='d-flex align-items-center gap-2'>
                            <div class='rounded-circle bg-light d-flex align-items-center justify-content-center p-2'>
                                <i class='bi bi-person {$color} fs-5'></i>
                            </div>
                            <div>
                                <strong class='d-block'>" . e((string)$member->get('name')) . "</strong>
                                <small class='text-muted'>" . e((string)$member->get('email')) . "</small>
                            </div>
                        </div>";
                    }),

                TD::make('role_label', 'Статус / Роль')
                    ->render(function (Repository $member) {
                        if ($member->get('is_self')) {
                            return "<span class='badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1'><i class='bi bi-shield-check me-1'></i>Власник (Повний доступ)</span>";
                        }

                        $role = (string)$member->get('role');
                        $roleTitle = (string)$member->get('role_title');
                        $nickname = (string)$member->get('nickname');

                        $badgeClass = ($role === BudgetShare::ROLE_VIEWER) 
                            ? 'bg-info-subtle text-info border border-info-subtle'
                            : 'bg-success-subtle text-success border border-success-subtle';
                        $icon = ($role === BudgetShare::ROLE_VIEWER) ? 'bi-eye' : 'bi-pencil-square';

                        $html = "<div class='d-flex flex-column gap-1'>
                            <span class='badge {$badgeClass} px-2 py-1 text-start'>
                                <i class='bi {$icon} me-1'></i>" . e($roleTitle) . "
                            </span>";
                        if ($nickname) {
                            $html .= "<small class='text-muted'><i class='bi bi-tag me-1'></i>" . e($nickname) . "</small>";
                        }
                        $html .= "</div>";
                        return $html;
                    }),

                TD::make('bills_count', 'Рахунки')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn (Repository $m) => "<span class='badge bg-secondary-subtle text-secondary px-2 py-1'>" . (int)$m->get('bills_count') . "</span>"),

                TD::make('transactions_count', 'Транзакції')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn (Repository $m) => "<span class='badge bg-secondary-subtle text-secondary px-2 py-1'>" . (int)$m->get('transactions_count') . "</span>"),

                TD::make('linked_at', 'Дата підключення')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn (Repository $m) => (string)$m->get('linked_at')),

                TD::make('actions', 'Дії')
                    ->align(TD::ALIGN_CENTER)
                    ->render(function (Repository $member) {
                        if ($member->get('is_self')) {
                            return "<span class='text-muted small'>Основний</span>";
                        }

                        $name = e((string)$member->get('name'));
                        $id = $member->get('id');

                        $editBtn = ModalToggle::make('Змінити роль')
                            ->icon('bs.shield-lock')
                            ->modal('editRoleModal')
                            ->method('updateRole')
                            ->asyncParameters(['partner_id' => $id])
                            ->class('btn btn-sm btn-outline-primary rounded-pill me-1');

                        $unlinkBtn = Button::make('Відключити')
                            ->icon('bs.x-circle')
                            ->confirm("Ви впевнені, що хочете розірвати спільний доступ із користувачем '{$name}'?")
                            ->method('unlinkUser', ['partner_id' => $id])
                            ->type(Color::DANGER)
                            ->class('btn btn-sm btn-outline-danger rounded-pill');

                        return "<div class='d-flex align-items-center justify-content-center gap-1'>" 
                            . $editBtn 
                            . $unlinkBtn 
                            . "</div>";
                    }),
            ])->title('Учасники спільного бюджету'),

            // Modal: Link existing user
            Layout::modal('linkUserModal', Layout::rows([
                Select::make('partner_id')
                    ->title('Оберіть зареєстрованого користувача')
                    ->options($this->query()['available_users'])
                    ->empty('— Оберіть зі списку користувачів —')
                    ->help('Оберіть акаунт користувача, з яким хочете вести спільний бюджет'),

                Input::make('partner_email')
                    ->type('email')
                    ->title('Або вкажіть Email користувача')
                    ->placeholder('user@example.com')
                    ->help('Якщо користувача немає у випадаючому списку вище'),

                Select::make('role')
                    ->title('Рівень доступу (Роль у бюджеті)')
                    ->options(BudgetShare::ROLES)
                    ->default(BudgetShare::ROLE_EDITOR)
                    ->help('Редактор має повний доступ, Спостерігач — тільки перегляд.'),

                Input::make('nickname')
                    ->title('Примітка / Нікнейм (необов\'язково)')
                    ->placeholder('напр. Дружина, Чоловік, Партнер')
                    ->help('Ця назва буде відображатися біля імені користувача'),
            ]))
                ->title('Підключення існуючого користувача')
                ->applyButton('Підключити'),

            // Modal: Create new user
            Layout::modal('createUserModal', Layout::rows([
                Input::make('user.name')
                    ->title('Ім\'я користувача')
                    ->required()
                    ->placeholder('напр. Олена')
                    ->help('Ім\'я нового учасника бюджету'),

                Input::make('user.email')
                    ->type('email')
                    ->title('Email')
                    ->required()
                    ->placeholder('olena@example.com'),

                Password::make('user.password')
                    ->title('Пароль')
                    ->required()
                    ->placeholder('Мінімум 6 символів')
                    ->help('Пароль для входу в систему нового користувача'),

                Select::make('role')
                    ->title('Рівень доступу (Роль у бюджеті)')
                    ->options(BudgetShare::ROLES)
                    ->default(BudgetShare::ROLE_EDITOR)
                    ->help('Редактор або Спостерігач'),

                Input::make('nickname')
                    ->title('Примітка / Нікнейм (необов\'язково)')
                    ->placeholder('напр. Дружина, Чоловік, Партнер')
                    ->help('Відображатиметься у списку учасників'),
            ]))
                ->title('Створення та підключення нового користувача')
                ->applyButton('Створити та підключити'),

            // Modal: Edit Role of partner
            Layout::modal('editRoleModal', Layout::rows([
                Input::make('edit_partner_id')->type('hidden'),
                Input::make('edit_partner_name')
                    ->title('Користувач')
                    ->readonly(),
                Select::make('edit_role')
                    ->title('Рівень доступу (Роль)')
                    ->options(BudgetShare::ROLES)
                    ->required()
                    ->help('Редактор може вносити операції, Спостерігач може тільки переглядати.'),
                Input::make('edit_nickname')
                    ->title('Примітка / Нікнейм')
                    ->placeholder('напр. Дружина, Чоловік, Партнер'),
            ]))
                ->async('asyncGetPartnerShare')
                ->title('Зміна ролі та прав учасника бюджету')
                ->applyButton('Зберегти права'),
        ];
    }

    /**
     * Link an existing user to the shared budget.
     */
    public function linkUser(Request $request): void
    {
        $currentUserId = Auth::id();
        $partnerId = $request->input('partner_id');
        $partnerEmail = $request->input('partner_email');

        $partner = null;
        if (!empty($partnerId)) {
            $partner = User::find($partnerId);
        } elseif (!empty($partnerEmail)) {
            $partner = User::where('email', trim($partnerEmail))->first();
        }

        if (!$partner) {
            Toast::error('Користувача не знайдено. Будь ласка, оберіть користувача або введіть коректний Email.');
            return;
        }

        if ($partner->id === $currentUserId) {
            Toast::error('Неможливо підключити самого себе.');
            return;
        }

        // Check already linked
        $exists = BudgetShare::where(function ($q) use ($currentUserId, $partner) {
            $q->where('user_id', $currentUserId)->where('partner_id', $partner->id);
        })->orWhere(function ($q) use ($currentUserId, $partner) {
            $q->where('user_id', $partner->id)->where('partner_id', $currentUserId);
        })->exists();

        if ($exists) {
            Toast::info("Користувач {$partner->name} вже підключений до вашого спільного бюджету.");
            return;
        }

        $role = $request->input('role', BudgetShare::ROLE_EDITOR);

        BudgetShare::create([
            'user_id' => $currentUserId,
            'partner_id' => $partner->id,
            'role' => $role,
            'can_create_transactions' => ($role !== BudgetShare::ROLE_VIEWER),
            'nickname' => $request->input('nickname') ?: 'Партнер',
        ]);

        Toast::success("Користувача '{$partner->name}' успішно додано до спільного бюджету з правами '{$role}'!");
    }

    /**
     * Create a new user account and automatically link to shared budget.
     */
    public function createUser(Request $request): void
    {
        $request->validate([
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email|unique:users,email',
            'user.password' => 'required|string|min:6',
        ]);

        $userData = $request->input('user');
        $nickname = $request->input('nickname');
        $role = $request->input('role', BudgetShare::ROLE_EDITOR);

        $systemRoleSlug = ($role === BudgetShare::ROLE_VIEWER) ? 'viewer' : 'partner';
        $systemRole = \Orchid\Platform\Models\Role::where('slug', $systemRoleSlug)->first();

        $newUser = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'permissions' => $systemRole?->permissions ?? [
                'platform.index' => '1',
                'platform.finance.dashboard' => '1',
            ],
        ]);

        if ($systemRole) {
            $newUser->roles()->attach($systemRole->id);
        }

        // Initialize default user settings and categories
        $this->initializeUserData($newUser->id);

        // Link with current user
        BudgetShare::create([
            'user_id' => Auth::id(),
            'partner_id' => $newUser->id,
            'role' => $role,
            'can_create_transactions' => ($role !== BudgetShare::ROLE_VIEWER),
            'nickname' => $nickname ?: 'Партнер',
        ]);

        Toast::success("Користувача '{$newUser->name}' успішно створено та додано до спільного бюджету!");
    }

    /**
     * Load partner info asynchronously for the role edit modal.
     */
    public function asyncGetPartnerShare(int $partner_id): iterable
    {
        $currentUserId = Auth::id();
        $share = BudgetShare::where(function ($q) use ($currentUserId, $partner_id) {
            $q->where('user_id', $currentUserId)->where('partner_id', $partner_id);
        })->orWhere(function ($q) use ($currentUserId, $partner_id) {
            $q->where('user_id', $partner_id)->where('partner_id', $currentUserId);
        })->first();

        $partner = User::find($partner_id);

        return [
            'edit_partner_id' => $partner_id,
            'edit_partner_name' => $partner ? "{$partner->name} ({$partner->email})" : '#' . $partner_id,
            'edit_role' => $share?->role ?? BudgetShare::ROLE_EDITOR,
            'edit_nickname' => $share?->nickname ?? '',
        ];
    }

    /**
     * Update permissions/role for an active partner.
     */
    public function updateRole(Request $request): void
    {
        $currentUserId = Auth::id();
        $partnerId = (int)$request->input('edit_partner_id', $request->input('partner_id'));
        $newRole = $request->input('edit_role', BudgetShare::ROLE_EDITOR);
        $newNickname = $request->input('edit_nickname');

        $share = BudgetShare::where(function ($q) use ($currentUserId, $partnerId) {
            $q->where('user_id', $currentUserId)->where('partner_id', $partnerId);
        })->orWhere(function ($q) use ($currentUserId, $partnerId) {
            $q->where('user_id', $partnerId)->where('partner_id', $currentUserId);
        })->first();

        if (!$share) {
            Toast::error('Зв\'язок не знайдено.');
            return;
        }

        $share->update([
            'role' => $newRole,
            'can_create_transactions' => ($newRole !== BudgetShare::ROLE_VIEWER),
            'nickname' => $newNickname ?: $share->nickname,
        ]);

        // Also sync system role if possible
        $partnerUser = User::find($partnerId);
        if ($partnerUser) {
            $systemRoleSlug = ($newRole === BudgetShare::ROLE_VIEWER) ? 'viewer' : 'partner';
            $systemRole = \Orchid\Platform\Models\Role::where('slug', $systemRoleSlug)->first();
            if ($systemRole && !$partnerUser->hasAccess('platform.systems.roles')) {
                $partnerUser->roles()->sync([$systemRole->id]);
                $partnerUser->permissions = $systemRole->permissions;
                $partnerUser->save();
            }
        }

        Toast::success('Права та роль учасника успішно оновлено!');
    }

    /**
     * Disconnect a partner from the shared budget.
     */
    public function unlinkUser(Request $request): void
    {
        $currentUserId = Auth::id();
        $partnerId = (int)$request->input('partner_id');

        if (!$partnerId) {
            Toast::error('Не вказано користувача для відключення.');
            return;
        }

        $deleted = BudgetShare::where(function ($q) use ($currentUserId, $partnerId) {
            $q->where('user_id', $currentUserId)->where('partner_id', $partnerId);
        })->orWhere(function ($q) use ($currentUserId, $partnerId) {
            $q->where('user_id', $partnerId)->where('partner_id', $currentUserId);
        })->delete();

        if ($deleted) {
            Toast::info('Спільний доступ успішно розірвано.');
        } else {
            Toast::warning('Зв\'язок не знайдено або вже було видалено.');
        }
    }

    /**
     * Initialize starter settings, default bill, and transaction categories for new user.
     */
    private function initializeUserData(int $userId): void
    {
        UserSetting::firstOrCreate([
            'user_id' => $userId,
        ], [
            'currency' => 'UAH',
        ]);

        FinanceBill::firstOrCreate([
            'user_id' => $userId,
            'name' => 'Готівка',
        ], [
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        $defaultCategories = [
            ['name' => 'Зарплата', 'transaction_type_id' => 2, 'user_id' => $userId],
            ['name' => 'Фріланс', 'transaction_type_id' => 2, 'user_id' => $userId],
            ['name' => 'Інше', 'transaction_type_id' => 2, 'user_id' => $userId],
            ['name' => 'Переказ', 'transaction_type_id' => 2, 'user_id' => $userId],
            ['name' => 'Їжа', 'transaction_type_id' => 1, 'user_id' => $userId],
            ['name' => 'Житло', 'transaction_type_id' => 1, 'user_id' => $userId],
            ['name' => 'Транспорт', 'transaction_type_id' => 1, 'user_id' => $userId],
            ['name' => 'Розваги', 'transaction_type_id' => 1, 'user_id' => $userId],
            ['name' => 'Комунальні послуги', 'transaction_type_id' => 1, 'user_id' => $userId],
            ['name' => 'Подарунки', 'transaction_type_id' => 1, 'user_id' => $userId],
            ['name' => 'Інше', 'transaction_type_id' => 1, 'user_id' => $userId],
        ];

        foreach ($defaultCategories as $cat) {
            FinanceTransactionCategory::firstOrCreate([
                'user_id' => $userId,
                'name' => $cat['name'],
                'transaction_type_id' => $cat['transaction_type_id'],
            ]);
        }
    }
}
