<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinancePaymentMethod;
use App\Models\FinanceSource;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionType;
use App\Models\Fop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Layouts\Rows;

class TransactionEditExpensesRows extends Rows
{
    /**
     * @var string
     */
    public $target = 'transaction';
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            Relation::make('transaction.transaction_category_id')
                ->title('Category')
                ->required()
                ->fromModel(FinanceTransactionCategory::class, 'name')
                ->applyScope('expenses'),
            Relation::make('transaction.finance_bill_id')
                ->title('Bills')
                ->required()
                ->fromModel(FinanceBill::class, 'name')
                ->displayAppend('billCurrency')
                ->applyScope('user'),
            Input::make("transaction.amount")
                ->title('Money spent')
                ->required()
                ->step(0.01)
                ->type('number'),

            TextArea::make("transaction.comment")
                ->title('Comment')
                ->value(''),
            DateTimer::make('transaction.created_at')
                ->title('Date created') ,
            Upload::make('transaction.attachment')
                ->title('Документи / Зображення'),

            Relation::make('transaction.fop_id')
                ->title('ФОП (для податків)')
                ->fromModel(Fop::class, 'name')
                ->applyScope('user')
                ->empty('— Не прив\'язувати до ФОП —')
                ->help('Оберіть ФОП, якщо це платіж сплати податку або збору'),

            Select::make('transaction.tax_type')
                ->title('Тип податку')
                ->options([
                    '' => '— Не податковий платіж —',
                    'single_tax'   => 'Єдиний податок (5%)',
                    'military_tax' => 'Військовий збір (1%)',
                    'esv'          => 'ЄСВ',
                ])
                ->help('Оберіть податок для врахування у податковому календарі'),

            Select::make('transaction.tax_quarter')
                ->title('Податковий квартал')
                ->options([
                    ''  => '— Без кварталу —',
                    '1' => 'I квартал',
                    '2' => 'II квартал',
                    '3' => 'III квартал',
                    '4' => 'IV квартал',
                ]),

            Input::make('transaction.tax_year')
                ->title('Податковий рік')
                ->type('number')
                ->min(2020)
                ->max(2050)
                ->placeholder((string)date('Y')),

            Input::make('transaction.transaction_type_id')
                ->value(1)
                ->hidden(),
            Input::make('transaction.type')
                ->value('expenses')
                ->hidden(),
        ];
    }
}
