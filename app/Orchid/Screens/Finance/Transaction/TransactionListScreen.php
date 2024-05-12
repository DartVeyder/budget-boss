<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditExpensesRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditIncomeRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditTransferRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
use App\Orchid\Layouts\Finance\Transaction\TransactionSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TransactionListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {

        return [
            "transactions" =>
                FinanceTransaction::filters(TransactionSelection::class)
                    ->where('transaction_type_id', "!=" , 3)
                    ->where('user_id' , Auth::user()->id)
                    ->defaultSort('id', 'desc')
                    ->paginate()
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Transactions';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Income'))
                ->modal('income')
                ->method('save'),
            ModalToggle::make(__('Expenses'))
                ->modal('expenses')
                ->method('save'),
            ModalToggle::make(__('Transfer'))
                ->modal('transfer')
                ->method('saveTransfer')
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
            TransactionSelection::class,
            TransactionListLayout::class,
            Layout::modal('income', [
                TransactionEditIncomeRows::class
            ])->title(__('New income')),
            Layout::modal('expenses', [
                TransactionEditExpensesRows::class
            ])->title(__('New expenses')),
            Layout::modal('transfer', [
                TransactionEditTransferRows::class
            ])->title(__('Transfer')),
        ];
    }

    public function save(Request $request, FinanceTransaction $transaction){
        $transaction->fill($request->input('transaction'))->save();
        Toast::info(__('You have successfully created.'));
    }

    public function  saveTransfer(Request $request){
        $data = $request->input('transaction');
        $bills =  $request->input('bills');

        if( $bills['with_bill_id'] ==  $bills['to_bill_id']){
            Toast::info(__('It is not possible to transfer to the same account'));
            return ;
        }

        $income =  $data;
        $income['type'] = 'expenses';
        $income['transaction_type_id'] = 3;
        $income['finance_bill_id']  =   $bills['with_bill_id'];
        FinanceTransaction::create($income);

        $expenses =  $data;
        $expenses['type'] = 'income';
        $expenses['transaction_type_id'] = 3;
        $expenses['finance_bill_id']  = $bills['to_bill_id'];
        FinanceTransaction::create($expenses);

    }
    public function remove(Request $request)
    {
        FinanceTransaction::findOrFail($request->get('id'))->delete();

        Toast::info(__('You have successfully remove'));
        return redirect()->route('platform.transactions');
    }
}
