<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinancePaymentMethod;
use App\Models\FinanceSource;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionType;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
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
            Relation::make('transaction_category_id')
                ->title('Category')
                ->required()
                ->fromModel(FinanceTransactionCategory::class, 'name')
                ->applyScope('expenses'),
            Relation::make('finance_bill_id')
                ->title('Bills')
                ->required()
                ->fromModel(FinanceBill::class, 'name')
                ->applyScope('user'),
            Input::make("amount")
                ->title('Money spent')
                ->required()
                ->type('number'),

            TextArea::make("comment")
                ->title('Comment')
                ->value(''),
            Input::make('transaction_type_id')
                ->value(2)
                ->hidden(),
            Input::make('type')
                ->value('expenses')
                ->hidden(),
            Input::make('user_id')
                ->value(Auth::user()->id)
                ->hidden(),
        ];
    }
}
