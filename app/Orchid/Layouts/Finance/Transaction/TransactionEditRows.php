<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Models\FinanceCurrencies;
use App\Models\FinancePaymentMethods;
use App\Models\FinanceSources;
use App\Models\FinanceTransactionCategories;
use App\Models\FinanceTransactionTypes;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class TransactionEditRows extends Rows
{
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
            Select::make('finance_transaction_category_id')
                ->title('Category')
                ->required()
                ->fromModel(FinanceTransactionCategories::class, 'name'),
            Select::make('finance_transaction_type_id')
                ->title('Type')
                ->required()
                ->fromModel(FinanceTransactionTypes::class, 'name'),
            Select::make('finance_payment_method_id')
                ->title('Payment method')
                ->required()
                ->fromModel(FinancePaymentMethods::class, 'name'),
            Select::make('finance_currency_id')
                ->required()
                ->title('Currency')
                ->fromModel(FinanceCurrencies::class, 'name'),
            Select::make('finance_source_id')
                ->title('Source')
                ->fromModel(FinanceSources::class, 'name')
                ->empty(''),
            Input::make("amount")
                ->title('Amount')
                ->required()
                ->type('number')
                ->value(0),
            DateTimer::make('expected_arrival_date')
                ->title('Expected arrival date')
                ->value(date('Y-m-d'))
                ->format('Y-m-d'),
            TextArea::make("description")
                ->title('Description')
                ->value('')
        ];
    }
}
