<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
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
                ->method('saveIncome'),
            ModalToggle::make(__('Expenses'))
                ->modal('expenses')
                ->method('saveExpenses'),
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

    public function saveIncome(Request $request, FinanceTransaction $financeTransaction): void{
        $transaction = $request->input('transaction');

        if($transaction['finance_invoice_id']){
            $invoice = FinanceInvoice::find($transaction['finance_invoice_id']);
            $transaction['accrual_date'] = $invoice->created_at;
        }

        $transaction = array_merge($transaction, $this->getCurrencyTransaction($transaction['finance_bill_id'], $transaction['amount']));

        $financeTransaction->fill($transaction)->save();
        $this->updateStatusInvoice($transaction['finance_invoice_id']);


        Toast::info(__('You have successfully created.'));
    }

    public function saveExpenses(Request $request, FinanceTransaction $financeTransaction): void{
        $transaction = $request->input('transaction');

        $transaction = array_merge($transaction, $this->getCurrencyTransaction($transaction['finance_bill_id'], $transaction['amount']));

        $financeTransaction->fill($transaction)->save();

        Toast::info(__('You have successfully created.'));
    }

    private function getCurrencyTransaction($bill_id, $amount){
        $bill = FinanceBill::find($bill_id);
        $currency = FinanceCurrency::find($bill->finance_currency_id);
        $transaction['finance_currency_id'] = $bill->finance_currency_id;
        $transaction['currency_code'] =  $bill->currency->code;
        $transaction['currency_value'] = $currency->value;
        $transaction['currency_amount'] = $amount * $currency->value;
        return $transaction;
    }

    private function updateStatusInvoice($invoice_id):void
    {
        if($invoice_id){
            $data = [];
            $invoice = FinanceInvoice::find($invoice_id);
            $amount_paid = FinanceTransaction::where('finance_invoice_id', $invoice_id)->get()->sum('amount');
            $data['amount_paid'] =  $amount_paid;
            if($amount_paid >= $invoice->total){
                $data['status'] = 'paid';
            }else{
                $data['status'] = 'not_paid';
            }

            FinanceInvoice::where('id', $invoice_id)->update($data);
        }

    }

    public function  saveTransfer(Request $request): void{
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
    public function remove(Request $request): object
    {
        $transaction = FinanceTransaction::findOrFail($request->get('id'));
        $transaction->delete();

        if($transaction->finance_invoice_id){
            $this->updateStatusInvoice($transaction->finance_invoice_id);
        }



        Toast::info(__('You have successfully remove'));
        return redirect()->route('platform.transactions');
    }
}
