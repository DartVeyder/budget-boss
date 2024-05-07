<?php
namespace App\Orchid\Layouts\Finance\Transaction;


use App\Models\FinanceTransactions;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class TransactionListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'transactions';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID')),
            TD::make('amount', __('Amount')),
            TD::make('finance_currency', __('Currency'))
                ->render(function ($call) {
                    dd($call->employee);
                    return $call->employee->name;
                }),

            TD::make('created_at', __('Created'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

        ];
    }
}
