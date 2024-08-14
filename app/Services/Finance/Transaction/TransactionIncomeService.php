<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;

class TransactionIncomeService extends  TransactionsService
{
    protected string $type = 'income';

    public function __construct() {
        $this->setType($this->type);
        parent::__construct();
    }
    public function createInsertData($request):array
    {
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
        $transaction['user_id'] = $this->getUserId();
        return $transaction;


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
}
