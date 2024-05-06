<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceTransactions;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditRows;
use App\Services\Finance\Transaction\TransactionService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class TransactionEditScreen extends Screen
{
    protected  $transactionService;
    public function __construct(TransactionService $transactionService){
        $this->transactionService = $transactionService;
    }
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransactions $transactions): iterable
    {
        return [
            'transactions' => $transactions
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Transactions add';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),
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
            TransactionEditRows::class
        ];
    }

    public function save(FinanceTransactions $transactions, Request $request): void{
        $this->transactionService->save($transactions, $request);
    }
}
