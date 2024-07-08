<?php

namespace App\Orchid\Layouts\Finance\Invoice;

use App\Models\Customer;
use App\Models\FinanceCurrency;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class InvoiceSaveRows extends Rows
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
            Relation::make('invoice.customer_id')
                ->title('From whom')
                ->required()
                ->fromModel(Customer::class, 'name')
                ->applyScope('user'),
            Select::make("invoice.finance_currency_id")
                ->required()
                ->fromModel(FinanceCurrency::class, 'name')
                ->title("Currency"),
            Input::make('invoice.total')
                ->title('Total')
                ->required()
                ->type('number')
                ->value(0),
            Input::make('invoice.user_id')
                ->value(Auth::user()->id)
                ->hidden(),
            TextArea::make('invoice.comment')
                ->title('Comment')
        ];
    }
}
