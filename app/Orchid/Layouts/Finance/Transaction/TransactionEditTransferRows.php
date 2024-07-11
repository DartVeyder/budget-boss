<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Models\FinanceBill;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class TransactionEditTransferRows extends Rows
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
            Relation::make('bills.with_bill_id')
                ->title('Bills')
                ->required()
                ->fromModel(FinanceBill::class, 'name')
                ->applyScope('user'),
            Relation::make('bills.to_bill_id')
                ->title('Bills')
                ->required()
                ->fromModel(FinanceBill::class, 'name')
                ->applyScope('user'),
            Input::make("transaction.amount")
                ->title('Amount')
                ->required()
                ->type('number') ,
            Input::make("transaction.transaction_category_id")
                ->value(1)
                ->hidden()
                ->type('number') ,
            Input::make("transaction.transaction_type_id")
                ->value(3)
                ->hidden()
                ->type('number') ,
            TextArea::make("transaction.comment")
                ->title('Comment')
                ->value(''),

        ];
    }
}
