<?php

namespace App\Orchid\Screens\Fop;

use App\Models\FinanceBill;
use App\Models\Fop;
use App\Models\User;
use App\Services\Finance\Act\DocumentGenerationService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Color;

class FopEditScreen extends Screen
{
    /**
     * @var Fop
     */
    public $fop;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @param Fop $fop
     *
     * @return array
     */
    public function query(Fop $fop): iterable
    {
        if ($fop->exists) {
            $fop->loadMissing(['attachment', 'bill', 'fopGroup']);
        }

        return [
            'fop' => $fop,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->fop->exists ? 'Редагування ФОП' : 'Створення ФОП';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Деталі, такі як ІПН, Рахунок та Адреса.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Створити')
                ->icon('bs.check-circle')
                ->type(Color::DEFAULT)
                ->method('createOrUpdate')
                ->canSee(!$this->fop->exists),

            Button::make('Оновити')
                ->icon('bs.check-circle')
                ->type(Color::DEFAULT)
                ->method('createOrUpdate')
                ->canSee($this->fop->exists),

            Button::make('Видалити')
                ->icon('bs.trash')
                ->type(Color::DANGER)
                ->method('remove')
                ->canSee($this->fop->exists),
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
            Layout::rows([
//                Relation::make('fop.user_id')
//                    ->title('Користувач')
//                    ->fromModel(User::class, 'name')
//                    ->required(),

                Input::make('fop.name')
                    ->title('Назва')
                    ->placeholder('Введіть назву ФОП')
                    ->required(),

                Input::make('fop.ipn')
                    ->title('ІПН')
                    ->placeholder('Введіть ІПН')
                    ->required(),

                Input::make('fop.ewn')
                    ->title('ЄДРПОУ')
                    ->placeholder('Введіть ЄДРПОУ (необов\'язково)'),

                Input::make('fop.address')
                    ->title('Адреса')
                    ->placeholder('Введіть адресу'),

                Input::make('fop.phone')
                    ->title('Телефон')
                    ->placeholder('+38 (098) 000-00-00')
                    ->help('Відображається в актах та рахунках'),

                Input::make('fop.iban')
                    ->title('IBAN рахунок')
                    ->placeholder('UA853220010000026005340151590')
                    ->help('Розрахунковий рахунок у форматі IBAN'),

                Input::make('fop.bank_name')
                    ->title('Назва банку')
                    ->placeholder('АТ «УНІВЕРСАЛ БАНК»'),

                Relation::make('fop.fop_group_id')
                    ->title('Група ФОП')
                    ->placeholder('Виберіть групу ФОП')
                    ->fromModel(\App\Models\FopGroup::class, 'name'),

                Relation::make('fop.finance_bill_id')
                    ->title('Рахунок / Картка')
                    ->fromModel(FinanceBill::class, 'name')
                    ->required(),

                Relation::make('fop.transaction_category_id')
                    ->fromModel(\App\Models\FinanceTransactionCategory::class, 'name')
                    ->applyScope('income')
                    ->title('Категорія доходу за замовчуванням'),

                Select::make('fop.tax_status')
                    ->options([
                        'without_taxes' => 'без податків',
                        'after_taxes' => 'після сплати податків',
                        'before_taxes'=> 'до сплати податків'
                    ])
                    ->empty('без податків','without_taxes')
                    ->title('Податковий статус'),



                Input::make('fop.director')
                    ->title('Директор')
                    ->placeholder('Введіть ім\'я директора (необов\'язково)'),

                Group::make([
                    Input::make('fop.contract_number')
                        ->title('Номер договору за замовчуванням')
                        ->placeholder('наприклад: МД18092026-01 або № 12/2026')
                        ->help('Автоматично підтягується в Акти та Рахунки на оплату'),

                    DateTimer::make('fop.contract_date')
                        ->title('Дата договору')
                        ->format('Y-m-d')
                        ->allowEmpty()
                        ->help('Дата укладання договору'),
                ]),

                Upload::make('fop.attachment')
                    ->title('Документ договору (файл)')
                    ->maxFiles(1)
                    ->acceptedFiles('.pdf,.docx,.doc,.jpg,.jpeg,.png')
                    ->help('Завантажте файл договору (PDF, DOCX). Якщо номер або дата не вказані, вони автоматично підтягнуться з документа'),

                CheckBox::make('fop.is_active')
                    ->title('Активний')
                    ->sendTrueOrFalse(),
            ])
        ];
    }

    /**
     * @param Fop    $fop
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(Fop $fop, Request $request)
    {
        $data = $request->get('fop');
        if (!$fop->exists) {
            $data['user_id'] = auth()->id();
        }

        $attachments = $request->input('fop.attachment', []);
        unset($data['attachment']);

        $fop->fill($data)->save();

        if (!empty($attachments)) {
            $fop->attachment()->syncWithoutDetaching($attachments);

            // Auto-extract contract details from document if empty
            if (empty($fop->contract_number) || empty($fop->contract_date)) {
                $fop->load('attachment');
                $att = $fop->attachment->first();
                if ($att) {
                    $docService = app(DocumentGenerationService::class);
                    $extracted = $docService->extractContractDetailsFromAttachment($att);
                    $updated = false;
                    if (empty($fop->contract_number) && !empty($extracted['number'])) {
                        $fop->contract_number = $extracted['number'];
                        $updated = true;
                    }
                    if (empty($fop->contract_date) && !empty($extracted['date'])) {
                        $fop->contract_date = $extracted['date'];
                        $updated = true;
                    }
                    if ($updated) {
                        $fop->save();
                    }
                }
            }
        }

        Alert::info('Ви успішно створили/оновили ФОП.');

        return redirect()->route('platform.fops');
    }

    /**
     * @param Fop $fop
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove(Fop $fop)
    {
        $fop->delete();

        Alert::info('Ви успішно видалили ФОП.');

        return redirect()->route('platform.fops');
    }
}
