<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Support\Facades\Toast;

trait TransactionService
{
    public function saveIncome(Request $request ): void{
        $transaction = $request->input('transaction');

        if(!$transaction['created_at']){
            unset($transaction['created_at']);
        }


        if(!$transaction['accrual_date']){
            unset($transaction['accrual_date']);
        }

        if($transaction['finance_invoice_id']){
            $invoice = FinanceInvoice::find($transaction['finance_invoice_id']);
            $transaction['accrual_date'] = $invoice->created_at;
        }

        $transaction = array_merge($transaction, $this->getCurrency($transaction['finance_bill_id'], $transaction['amount']));

        $transaction['tax_amount'] =  $this->calculateTaxAmount($transaction['currency_amount'], $request->input('tax_status'), (int)$request->input('tax_rates') );

        Auth::user()->transactions()->create($transaction );

        $this->updateStatusInvoice($transaction['finance_invoice_id']);

        Toast::info(__('You have successfully created.'));
    }

    private function calculateTaxAmount(float $amount,string|null  $status = 'without_taxes', int|null $rate = 0 ): float|int{
        $listRates = [
            0 => 0,
            1 => 5,
            2  => 19.5
        ];


        if( $status == 'without_taxes'){
            return  0;
        }
        if( $rate == 0){
            return  0;
        }

        if($status  == 'after_taxes'){
            return ($amount / (1 - $listRates[$rate] / 100)) - $amount;
        }

        if($status  == 'before_taxes'){
            return $amount * ($listRates[$rate] / 100);
        }

        return 0;
    }

    public function saveExpenses(Request $request): void{
        $transaction = $request->input('transaction');
        if(!$transaction['created_at']){
            unset($transaction['created_at']);
        }

        $transaction['amount'] = $this->getAmountNegative($transaction['amount']);
        $transaction = array_merge($transaction, $this->getCurrency($transaction['finance_bill_id'], $transaction['amount']));

        Auth::user()->transactions()->create($transaction);

        Toast::info(__('You have successfully created.'));
    }

    public function  saveTransfer(Request $request): void{
        $transaction = $request->input('transaction');
        $bills =  $request->input('bills');

        if(FinanceBill::find($bills['with_bill_id'])->finance_currency_id  != FinanceBill::find($bills['to_bill_id'])->finance_currency_id ){
            Toast::info(__('Transfers between accounts with different currencies are prohibited'));
            return ;
        }

        if( $bills['with_bill_id'] ==  $bills['to_bill_id']){
            Toast::info(__('It is not possible to transfer to the same account'));
            return ;
        }

        $income =  $transaction;
        $income['type'] = 'income';
        $income['finance_bill_id']  =  $bills['to_bill_id'];

        $income = array_merge(  $income,$this->getCurrency($bills['to_bill_id'], $transaction['amount']));

        $expenses =  $transaction;
        $expenses['type'] = 'expenses';
        $expenses['finance_bill_id']  = $bills['with_bill_id'];

        $expenses = array_merge(  $expenses,$this->getCurrency($bills['with_bill_id'],  $income['currency_amount']));
        $expenses['amount'] = $this->getAmountNegative( $expenses['amount']);
        $expenses['currency_amount'] = $this->getAmountNegative( $expenses['currency_amount']);


        Auth::user()->transactions()->create($expenses);
        Auth::user()->transactions()->create($income);

    }

    public function  saveAudit(Request $request){
        $data = [];
        $transaction = $request->input('transaction');
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
        $data = array_merge(  $data,$this->getCurrency( $transaction['bill_id'],  $diffTotal));

        Auth::user()->transactions()->create($data);
        Toast::info(__('You have successfully created.'));
    }

    private function updateStatusInvoice(int|null $invoice_id):void
    {
        if($invoice_id){
            $data = [];
            $invoice = FinanceInvoice::find($invoice_id);
            $amount_paid = FinanceTransaction::where('finance_invoice_id', $invoice_id)->get()->sum('amount');
            $data['amount_paid'] =  $amount_paid;
            if($amount_paid >= $invoice->total){
                $data['status'] = 'paid';
            }else if($amount_paid < $invoice->total){
                $data['status'] = 'part paid';
            }else{
                $data['status'] = 'not paid';
            }

            FinanceInvoice::where('id', $invoice_id)->update($data);
        }

    }

    private function getCurrency(int $bill_id, float $amount): array{
        $bill = FinanceBill::find($bill_id);
        $currency = FinanceCurrency::find($bill->finance_currency_id);
        $transaction['finance_currency_id'] = $bill->finance_currency_id;
        $transaction['currency_code'] =  $bill->currency->code;
        $transaction['currency_value'] = $currency->value;
        $transaction['currency_amount'] = $amount * $currency->value  ;
        $transaction['absolute_currency_amount'] = abs($amount * $currency->value);
        return $transaction;
    }

    private function getAmountNegative(float $amount): float{
         return  -abs($amount);
    }

}
