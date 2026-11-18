<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Models\FinanceBill;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Layouts\Rows;

class TransactionEditAuditRows extends Rows
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
            Relation::make('transaction.bill_id')
                ->title('Рахунок')
                ->required()
                ->fromModel(FinanceBill::class, 'name')
                ->applyScope('user'),
            Input::make("transaction.current_balance")
                ->title('Фактичний / початковий баланс')
                ->placeholder('0.00')
                ->required()
                ->step(0.01)
                ->type('number'),
            TextArea::make("transaction.comment")
                ->title('Коментар')
                ->placeholder('напр. Початковий баланс або Ревізія')
                ->value(''),
            Input::make('transaction.user_id')
                ->value(Auth::user()->id)
                ->hidden(),
            DateTimer::make('transaction.created_at')
                ->title('Дата')
                ->enableTime(),
            Upload::make('transaction.attachment')
                ->title('Документи / Зображення'),

        ];
    }
}
