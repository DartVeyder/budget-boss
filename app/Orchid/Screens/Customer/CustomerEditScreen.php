<?php

namespace App\Orchid\Screens\Customer;

use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Orchid\Layouts\Customer\CustomerCounterpartyEditLayout;
use App\Orchid\Layouts\Customer\CustomerCounterpartyListLayout;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Illuminate\Http\Request;

class CustomerEditScreen extends Screen
{
    /**
     * @var Customer
     */
    public $customer;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Customer $customer): iterable
    {
        if ($customer->exists && $customer->user_id !== auth()->id()) {
            abort(403);
        }

        $this->customer = $customer;

        return [
            'customer' => $customer,
            'counterparties' => $customer->exists ? $customer->counterparties()->latest()->paginate(15) : collect(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->customer?->exists ? 'Редагування клієнта' : 'Створення клієнта';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $isExists = (bool)($this->customer?->exists);

        return [
            ModalToggle::make('Додати ФОП контрагента')
                ->icon('bs.person-plus')
                ->modal('asyncEditCounterpartyModal')
                ->modalTitle('Новий ФОП контрагент платник')
                ->method('saveCounterparty')
                ->canSee($isExists),

            Button::make('Створити')
                ->icon('pencil')
                ->method('createOrUpdate')
                ->canSee(!$isExists),

            Button::make('Оновити')
                ->icon('note')
                ->method('createOrUpdate')
                ->canSee($isExists),

            Button::make('Видалити')
                ->icon('trash')
                ->method('remove')
                ->confirm('Ви впевнені, що хочете видалити клієнта?')
                ->canSee($isExists),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $isExists = (bool)($this->customer?->exists);

        $layouts = [
            Layout::block(
                Layout::rows([
                    Input::make('customer.name')
                        ->title('Ім\'я / Назва клієнта')
                        ->placeholder('Введіть ім\'я клієнта')
                        ->required(),

                    Input::make('customer.email')
                        ->title('Email')
                        ->placeholder('Введіть email')
                        ->type('email'),

                    Input::make('customer.phone')
                        ->title('Телефон')
                        ->placeholder('Введіть номер телефону'),

                    CheckBox::make('customer.is_fop')
                        ->title('Це ФОП?')
                        ->sendTrueOrFalse(),

                    Group::make([
                        Select::make('customer.tax_group')
                            ->title('Група єдиного податку')
                            ->options([
                                '' => 'Без групи',
                                '1 група' => '1 група',
                                '2 група' => '2 група',
                                '3 група' => '3 група',
                                '4 група' => '4 група',
                            ])
                            ->empty('Не вказано')
                            ->help('Вкажіть групу ФОП'),

                        CheckBox::make('customer.is_single_tax')
                            ->title('Платник єдиного податку')
                            ->sendTrueOrFalse()
                            ->value(true),

                        CheckBox::make('customer.is_vat_payer')
                            ->title('Платник ПДВ')
                            ->sendTrueOrFalse()
                            ->value(false)
                            ->placeholder('Платник ПДВ (інакше Не платник ПДВ)'),
                    ]),

                    Group::make([
                        Input::make('customer.ipn')
                            ->title('ІПН')
                            ->placeholder('ІПН'),

                        Input::make('customer.edrpou')
                            ->title('ЄДРПОУ')
                            ->placeholder('ЄДРПОУ (для компаній)'),
                    ]),

                    TextArea::make('customer.address')
                        ->title('Адреса')
                        ->placeholder('Юридична адреса')
                        ->rows(3),

                    Group::make([
                        Input::make('customer.director')
                            ->title('Директор')
                            ->placeholder('ПІБ Директора'),
                    ]),

                    Group::make([
                        Input::make('customer.bank_name')
                            ->title('Назва банку')
                            ->placeholder('Назва банку'),

                        Input::make('customer.mfo')
                            ->title('МФО')
                            ->placeholder('МФО'),
                    ]),

                    Input::make('customer.iban')
                        ->title('IBAN')
                        ->placeholder('IBAN'),

                    \Orchid\Screen\Fields\Relation::make('customer.fop_id')
                        ->fromModel(\App\Models\Fop::class, 'name')
                        ->applyScope('user')
                        ->title('Прив\'язати мій ФОП')
                        ->help('Виберіть ФОП, щоб не вказувати рахунок та податкові налаштування вручну'),
                ])
            )
            ->title('Основні дані клієнта')
            ->description('Контактна та юридична інформація клієнта')
            ->commands([
                Button::make($isExists ? 'Оновити дані' : 'Створити клієнта')
                    ->type(Color::BASIC)
                    ->icon('bs.check-circle')
                    ->method('createOrUpdate'),
            ]),
        ];

        if ($isExists) {
            $layouts[] = Layout::block(
                CustomerCounterpartyListLayout::class
            )
            ->title('ФОПи контрагенти (платники)')
            ->description('Список ФОПів/платників, від імені яких цей клієнт здійснює оплати. Ви зможете обирати конкретного платника при внесенні доходів.')
            ->commands([
                ModalToggle::make('Додати ФОП контрагента')
                    ->type(Color::BASIC)
                    ->icon('bs.person-plus')
                    ->modal('asyncEditCounterpartyModal')
                    ->modalTitle('Новий ФОП контрагент платник')
                    ->method('saveCounterparty'),
            ]);

            $layouts[] = Layout::modal('asyncEditCounterpartyModal', CustomerCounterpartyEditLayout::class)
                ->async('asyncGetCounterparty')
                ->title('ФОП контрагент клієнта')
                ->applyButton('Зберегти');
        }

        return $layouts;
    }

    /**
     * @param Customer    $customer
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(Customer $customer, Request $request)
    {
        if ($customer->exists && $customer->user_id !== auth()->id()) {
            abort(403);
        }

        $customer->fill($request->get('customer'));
        $customer->user_id = auth()->id(); 
        $customer->save();

        Toast::info('Клієнта успішно створено/оновлено.');

        return redirect()->route('platform.customers');
    }

    /**
     * @param Customer $customer
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove(Customer $customer)
    {
        if ($customer->exists && $customer->user_id !== auth()->id()) {
            abort(403);
        }

        $customer->delete();

        Toast::info('Клієнта успішно видалено.');

        return redirect()->route('platform.customers');
    }

    /**
     * Async load counterparty for modal edit.
     *
     * @param Request $request
     * @return iterable
     */
    public function asyncGetCounterparty(Request $request): iterable
    {
        $counterpartyId = $request->input('counterparty');
        $counterparty = $counterpartyId
            ? CustomerCounterparty::where('user_id', auth()->id())->with('attachment')->find($counterpartyId)
            : new CustomerCounterparty(['is_active' => true]);

        return [
            'counterparty' => $counterparty ?? new CustomerCounterparty(['is_active' => true]),
        ];
    }

    /**
     * Save counterparty (create or update).
     *
     * @param Customer $customer
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveCounterparty(Customer $customer, Request $request)
    {
        if ($customer->exists && $customer->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'counterparty.name' => 'required|string|max:255',
            'counterparty.ipn' => 'nullable|string|max:20',
            'counterparty.iban' => 'nullable|string|max:34',
            'counterparty.bank_name' => 'nullable|string|max:255',
            'counterparty.phone' => 'nullable|string|max:50',
            'counterparty.address' => 'nullable|string|max:1000',
            'counterparty.tax_group' => 'nullable|string|max:50',
            'counterparty.contract_number' => 'nullable|string|max:255',
            'counterparty.contract_date' => 'nullable|date',
            'counterparty.is_single_tax' => 'boolean',
            'counterparty.is_vat_payer' => 'boolean',
            'counterparty.notes' => 'nullable|string|max:1000',
            'counterparty.is_active' => 'boolean',
        ]);

        $data = $request->input('counterparty', []);
        $counterpartyId = $data['id'] ?? null;
        unset($data['id']);

        $attachments = $data['attachment'] ?? [];
        unset($data['attachment']);

        if (empty($data['contract_date'])) {
            $data['contract_date'] = null;
        }

        $counterparty = $counterpartyId
            ? CustomerCounterparty::where('user_id', auth()->id())->findOrFail($counterpartyId)
            : new CustomerCounterparty();

        $counterparty->fill($data);
        $counterparty->customer_id = $customer->id;
        $counterparty->user_id = auth()->id();
        $counterparty->save();

        if (!empty($attachments)) {
            $counterparty->attachment()->syncWithoutDetaching($attachments);

            // Auto-extract contract details from document if empty
            if (empty($counterparty->contract_number) || empty($counterparty->contract_date)) {
                $counterparty->load('attachment');
                $att = $counterparty->attachment->first();
                if ($att) {
                    $docService = app(\App\Services\Finance\Act\DocumentGenerationService::class);
                    $extracted = $docService->extractContractDetailsFromAttachment($att);
                    $updated = false;
                    if (empty($counterparty->contract_number) && !empty($extracted['number'])) {
                        $counterparty->contract_number = $extracted['number'];
                        $updated = true;
                    }
                    if (empty($counterparty->contract_date) && !empty($extracted['date'])) {
                        $counterparty->contract_date = $extracted['date'];
                        $updated = true;
                    }
                    if ($updated) {
                        $counterparty->save();
                    }
                }
            }
        }

        Toast::info('ФОП контрагента успішно збережено.');

        return redirect()->route('platform.customers.edit', $customer);
    }

    /**
     * Delete counterparty.
     *
     * @param Customer $customer
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteCounterparty(Customer $customer, Request $request)
    {
        if ($customer->exists && $customer->user_id !== auth()->id()) {
            abort(403);
        }

        $counterparty = CustomerCounterparty::where('user_id', auth()->id())
            ->where('customer_id', $customer->id)
            ->findOrFail($request->input('id'));

        $counterparty->delete();

        Toast::info('ФОП контрагента видалено.');

        return redirect()->route('platform.customers.edit', $customer);
    }
}
