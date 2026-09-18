<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditAuditRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditExpensesRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditIncomeRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditTransferRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
use App\Orchid\Layouts\Finance\Transaction\TransactionSelection;
use App\Services\Api\Monobank\Monobank;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use App\Services\Finance\Transaction\TransactionService;
use App\Services\Finance\Transaction\TransactionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TransactionListScreen extends Screen
{
   //use TransactionService;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {

        return [
            "transactions" =>
                FinanceTransaction::with(['attachment', 'customer', 'counterparty', 'bill', 'category', 'currency'])->filters(TransactionSelection::class)
                    ->where('user_id' , Auth::user()->id)
                    ->defaultSort('created_at', 'desc')
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
                ->method('saveTransfer'),
            ModalToggle::make(__('Audit'))
                ->modal('audit')
                ->method('saveAudit'),
            Button::make("Mono")
                ->method("saveMono")
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
            Layout::modal('audit', [
                TransactionEditAuditRows::class
            ])->title(__('Audit')),

        ];
    }



    public function  saveIncome(Request $request, FinanceTransaction $transaction,TransactionIncomeService $transactionIncomeService):void
    {
        $data = $transactionIncomeService->createInsertData($request);
        $taxDetails = $data['tax_details'] ?? [];
        unset($data['attachment'], $data['tax_details']);
        
        $transaction->fill($data);
        unset($transaction->tax_details);
        $transaction->save();
        
        $transaction->attachment()->syncWithoutDetaching(
            $request->input('transaction.attachment', [])
        );
        
        if (!empty($taxDetails)) {
            $transaction->taxes()->sync($taxDetails);
        } else {
            $transaction->taxes()->detach();
        }
        
        $transactionIncomeService->updateStatusInvoice($data['finance_invoice_id'] ?? null);
        Toast::info(__('You have successfully created.'));
    }

    public function  saveExpenses(Request $request, FinanceTransaction $transaction, TransactionExpensesService $transactionExpensesService ):void
    {
        $data = $transactionExpensesService->createInsertData($request);
        unset($data['attachment']);
        $transaction->fill($data)->save($data);
        $transaction->attachment()->syncWithoutDetaching(
            $request->input('transaction.attachment', [])
        );

        Toast::info(__('You have successfully created.'));
    }

    public function  saveTransfer(Request $request,  TransactionsService  $transactionsService): void{
        $transaction = $request->input('transaction', []);
        $attachments = $transaction['attachment'] ?? [];
        unset($transaction['attachment']);
        $transaction['is_balance'] = 0;
        $bills =  $request->input('bills', []);

        if (empty($bills['with_bill_id']) || empty($bills['to_bill_id'])) {
            Toast::error(__('Будь ласка, виберіть обидва рахунки для переказу'));
            return;
        }

        $withBill = FinanceBill::where('user_id', Auth::id())->find($bills['with_bill_id']);
        $toBill = FinanceBill::where('user_id', Auth::id())->find($bills['to_bill_id']);

        if (!$withBill || !$toBill) {
            Toast::error(__('Рахунок не знайдено'));
            return;
        }

        if ($withBill->finance_currency_id != $toBill->finance_currency_id) {
            Toast::info(__('Transfers between accounts with different currencies are prohibited'));
            return ;
        }

        if ($bills['with_bill_id'] == $bills['to_bill_id']) {
            Toast::info(__('It is not possible to transfer to the same account'));
            return ;
        }

        $amount = (float)($transaction['amount'] ?? 0);

        $income =  $transaction;
        $income['type'] = 'income';
        $income['finance_bill_id']  =  $bills['to_bill_id'];
        $income = array_merge($income, $transactionsService->getCurrency((int)$bills['to_bill_id'], $amount));

        $expenses =  $transaction;
        $expenses['type'] = 'expenses';
        $expenses['finance_bill_id']  = $bills['with_bill_id'];
        $expenses = array_merge($expenses, $transactionsService->getCurrency((int)$bills['with_bill_id'], $amount));
        $expenses['amount'] = $transactionsService->getAmountNegative($expenses['amount']);
        $expenses['currency_amount'] = $transactionsService->getAmountNegative($expenses['currency_amount']);

        $exp = Auth::user()->transactions()->create($expenses);
        $inc = Auth::user()->transactions()->create($income);

        if (!empty($attachments)) {
            $exp->attachment()->syncWithoutDetaching($attachments);
            $inc->attachment()->syncWithoutDetaching($attachments);
        }

        Toast::info(__('You have successfully created.'));
    }

    public function  saveAudit(Request $request,  TransactionsService  $transactionsService){

        $data = [];
        $transaction = $request->input('transaction');
        $attachments = $transaction['attachment'] ?? [];
        unset($transaction['attachment']);
        $total =  Auth::user()->transactions()->where('finance_bill_id',  $transaction['bill_id'])->sum('amount') ;

        $diffTotal = $transaction['current_balance'] - $total;
        $data['amount'] =  $diffTotal ;
        $data['type'] =  ( $diffTotal > 0) ? 'income': 'expenses';

        $data['transaction_type_id'] = 4;
        $data['transaction_category_id'] =  2;
        if($transaction['created_at']){
            $data['accrual_date'] = $transaction['created_at'];
            $data['created_at'] = $transaction['created_at'];
        }
        $data['finance_bill_id'] = $transaction['bill_id'];
        $data = array_merge(  $data,$transactionsService->getCurrency( $transaction['bill_id'],  $diffTotal));

        $auditTransaction = Auth::user()->transactions()->create($data);
        if (!empty($attachments)) {
            $auditTransaction->attachment()->syncWithoutDetaching($attachments);
        }
        Toast::info(__('You have successfully created.'));
    }
    public function remove(Request $request,TransactionsService  $transactionsService): object
    {
        $transaction = FinanceTransaction::findOrFail($request->get('id'));
        $transaction->delete();

        if($transaction->finance_invoice_id){
            $transactionsService->updateStatusInvoice($transaction->finance_invoice_id);
        }

        Toast::info(__('You have successfully remove'));
        return redirect()->back();
    }

    public function saveMono( TransactionExpensesService $transactionExpensesService )
    {
        $setting = Auth::user()->setting;
        if (!$setting || !$setting->monobank_active || empty($setting->monobank_api_key)) {
            Toast::warning(__('Please configure your Monobank API key in settings first.'));
            return;
        }

        $token = $setting->monobank_api_key;

        $transactions = FinanceTransaction::where('user_id', Auth::id())
            ->whereNotNull('mono_id')
            ->orderBy('id', 'DESC')
            ->first();


        if( is_null($transactions)){
            $from = Carbon::now()->startOfMonth();
        }else{
            $from = $transactions->created_at;
        }

        $to = Carbon::now();


        $statement = Monobank::getStatement($from,  $to, $token);
        if($statement){
            $data = $transactionExpensesService->createInsertDataMono($statement);
            if(FinanceTransaction::insert($data)){
                Toast::info(__('Успішно імпортовано '. count($data) .' транзакцій з Моно'));
            }
        }else{
            Toast::info(__('Нема нових транзакцій з Моно'));
        }




    }
}
