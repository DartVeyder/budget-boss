<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceTransaction;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

class TransactionCardScreen extends Screen
{
    /**
     * @var
     */
    public $transaction;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransaction $transaction): array
    {
        return [
            'transaction' => $transaction
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Transaction') . ' від ' . $this->transaction->created_at->format('d.m.Y H:i') . ' ('.  $this->transaction->transactionType->name . ")";
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::legend('transaction', [
                Sight::make('id', "ID"),
                Sight::make('transactionCategory.name', __("Category"))
                    ->render(
                        fn() =>  $this->transaction->transactionCategory->name
                    ),
                Sight::make('transactionType.name', __("Type")),
                Sight::make('amount', __("Amount"))
                    ->render(
                        fn() => view('finance.transaction.partials.amount', ['amount' => $this->transaction->amount , 'currency' =>  $this->transaction->currency->code] )
                    ),
                Sight::make('balance', __("Balance"))
                    ->render(
                        fn() => $this->transaction->balance . " " . $this->transaction->currency->code
                    ),
                Sight::make('description', __("Description")),
            ])
        ];
    }
}
