<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditRows;
use App\Services\Finance\Transaction\TransactionService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

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
    public function query(FinanceTransaction $transactions): iterable
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

    public function save(FinanceTransaction $transactions, Request $request){
        $this->transactionService->save($transactions, $request);
        Toast::info(__('You have successfully created a transaction.'));
        return redirect()->route('platform.transactions');

    }
}
