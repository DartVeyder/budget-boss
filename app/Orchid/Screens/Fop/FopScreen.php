<?php

namespace App\Orchid\Screens\Fop;

use App\Models\FinanceBill;
use App\Models\Fop;
use App\Services\Finance\Act\DocumentGenerationService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Color;

class FopScreen extends Screen
{
    /**
     * @var Fop
     */
    public $fop;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $fop = Fop::where('user_id', auth()->id())->first() ?? new Fop();
        $this->fop = $fop;

        $limitProgress = null;
        if ($fop->exists) {
            $fop->loadMissing(['attachment', 'bill', 'fopGroup']);
            if (empty($fop->iban) && !empty($fop->bill?->iban)) {
                $fop->iban = $fop->bill->iban;
            }
            if (empty($fop->bank_name) && !empty($fop->bill?->bank_name)) {
                $fop->bank_name = $fop->bill->bank_name;
            }
            $taxService = new \App\Services\Finance\Fop\FopTaxService();
            $limitProgress = $taxService->getLimitProgress($fop);
        }

        return [
            'fop' => $fop,
            'limitProgress' => $limitProgress,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Мій ФОП';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Деталі ФОП (Фізична особа-підприємець): ІПН, рахунок, ліміти, адреса.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            \Orchid\Screen\Actions\Link::make('Податки та Календар')
                ->icon('bs.calculator')
                ->route('platform.fop.tax')
                ->canSee($this->fop->exists),

            \Orchid\Screen\Actions\Link::make('Книга доходів')
                ->icon('bs.book')
                ->route('platform.fop.ledger')
                ->canSee($this->fop->exists),

            Button::make('Зберегти')
                ->icon('bs.check-circle')
                ->type(Color::DEFAULT)
                ->method('save'),

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
            Layout::view('fop.limit-progress-widget')->canSee($this->fop?->exists ?? false),

            Layout::rows([
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
                    ->help('Відображається в реквізитах актів та рахунків на оплату'),

                Input::make('fop.iban')
                    ->title('IBAN рахунок')
                    ->placeholder('UA853220010000026005340151590')
                    ->help('Розрахунковий рахунок у форматі IBAN для актів та рахунків на оплату'),

                Input::make('fop.bank_name')
                    ->title('Назва банку')
                    ->placeholder('АТ «УНІВЕРСАЛ БАНК»')
                    ->help('Офіційна назва банку для рахунків та договорів'),

                Relation::make('fop.fop_group_id')
                    ->title('Група ФОП')
                    ->placeholder('Виберіть групу ФОП')
                    ->fromModel(\App\Models\FopGroup::class, 'name'),

                Input::make('fop.annual_limit')
                    ->type('number')
                    ->step('0.01')
                    ->title('Персональний річний ліміт доходу (грн)')
                    ->placeholder('Використовувати ліміт групи')
                    ->help('Залишіть порожнім, щоб автоматично застосовувався ліміт із вибраної групи ФОП.'),

                Input::make('fop.custom_esv')
                    ->type('number')
                    ->step('0.01')
                    ->title('Персональна щомісячна ставка ЄСВ (грн)')
                    ->placeholder('1760.00')
                    ->help('Залишіть порожнім, щоб брати ставку з групи ФОП.'),

                CheckBox::make('fop.is_esv_exempt')
                    ->title('Звільнення від ЄСВ')
                    ->placeholder('Не нараховувати та не сплачувати ЄСВ (пенсіонер, особа з інвалідністю, за основним місцем роботи, або добровільна несплата у воєнний стан)')
                    ->sendTrueOrFalse(),

                Relation::make('fop.finance_bill_id')
                    ->title('Рахунок / Картка')
                    ->fromModel(FinanceBill::class, 'name')
                    ->applyScope('user')
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
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        $fop = Fop::firstOrNew(['user_id' => auth()->id()]);

        $data = $request->get('fop');
        $data['user_id'] = auth()->id();

        $attachments = $request->input('fop.attachment', []);
        unset($data['attachment']);

        $fop->fill($data)->save();

        if (!empty($attachments)) {
            $fop->attachment()->syncWithoutDetaching($attachments);

            // Auto-extract contract number and date from document if not provided
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

        if ($fop->finance_bill_id && $fop->bill) {
            if (!empty($fop->iban)) {
                $fop->bill->iban = $fop->iban;
            }
            if (!empty($fop->bank_name)) {
                $fop->bill->bank_name = $fop->bank_name;
            }
            $fop->bill->save();
        }

        Alert::info('ФОП успішно збережено.');

        return redirect()->route('platform.fops');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove()
    {
        $fop = Fop::where('user_id', auth()->id())->first();

        if ($fop) {
            $fop->delete();
            Alert::info('ФОП успішно видалено.');
        }

        return redirect()->route('platform.fops');
    }
}
