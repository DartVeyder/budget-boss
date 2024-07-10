<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
use App\Models\FinancePaymentMethod;
use App\Models\FinanceSource;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionType;
use App\Orchid\Screens\Components\Cells\DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class TransactionEditIncomeRows extends Rows
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
                ->applyScope('income'),
            Relation::make('transaction.finance_invoice_id')
                ->title('Invoice')
                ->fromModel(FinanceInvoice::class, 'invoice_number'),
            Relation::make('transaction.finance_bill_id')
                ->title('Bills')
                ->required()
                ->displayAppend('billCurrency')
                ->fromModel(FinanceBill::class, 'name')
                ->applyScope('user'),
            Input::make("transaction.amount")
                ->title('Top-up amount')
                ->required()
                ->step(0.01)
                ->type('number') ,
            Select::make("tax_status")
            ->options([
                'without_taxes' => 'без податків',
                'after_taxes' => 'після сплати податків',
                'before_taxes'=> 'до сплати податків'
            ])
                ->empty('без податків','without_taxes')
                ->title('Tax status'),
            Select::make("tax_rates")
                ->options([
                    '0' => '0%',
                    '1' => '5%',
                    '2'=> '19.5%'
                ])
                ->empty('0','0%')

                ->title('Tax rates'),
            DateTimer::make('transaction.accrual_date')
                ->title('Date created')
                ->value(Carbon::now()),
            TextArea::make("transaction.comment")
                ->title('Comment')
                ->value(''),
            Input::make('transaction.transaction_type_id')
                ->value(2)
                ->hidden(),
            Input::make('transaction.type')
                ->value('income')
                ->hidden()
        ];
    }
}
