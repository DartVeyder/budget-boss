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

        if ($transaction['customer_id']) {
            $customer = \App\Models\Customer::find($transaction['customer_id']);
            if ($customer) {
                if (empty($transaction['finance_bill_id'])) {
                    $transaction['finance_bill_id'] = $customer->finance_bill_id;
                }

                if (empty($transaction['transaction_category_id'])) {
                    $transaction['transaction_category_id'] = $customer->transaction_category_id;
                }
                
                $taxStatus = $request->input('tax_status');
                if (empty($taxStatus) || $taxStatus == 'without_taxes') {
                    $taxStatus = $customer->tax_status ?? 'without_taxes';
                }

                $taxRateId = $request->input('tax_rates');
                if (empty($taxRateId)) {
                    $taxRateId = $customer->tax_rate_id;
                }
            }
        } else {
            $taxStatus = $request->input('tax_status');
            $taxRateId = $request->input('tax_rates');
        }

        // Validation: ensure we have a bill and a category before proceeding with calculations
        if (empty($transaction['finance_bill_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'transaction.finance_bill_id' => 'Будь ласка, виберіть рахунок (або вкажіть його у налаштуваннях замовника)'
            ]);
        }

        if (empty($transaction['transaction_category_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'transaction.transaction_category_id' => 'Будь ласка, виберіть категорію доходу (або вкажіть її у налаштуваннях замовника)'
            ]);
        }

        $transaction = array_merge($transaction, $this->getCurrency($transaction['finance_bill_id'], $transaction['amount']));
        $transaction['balance'] = $this->getTotalBalance() +  $transaction['amount'];
        $transaction['balance_bill'] = $this->getBalanceToBill($transaction['finance_bill_id']) +  $transaction['amount'];
        $transaction['tax_amount'] =  $this->calculateTaxAmount($transaction['currency_amount'], $taxStatus, (int)$taxRateId );
        $transaction['user_id'] = $this->getUserId();

        return $transaction;


    }

    private function calculateTaxAmount(float $amount, string|null $status = 'without_taxes', int|null $rateId = null): float|int
    {
        if ($status == 'without_taxes' || !$rateId) {
            return 0;
        }

        $taxRate = \App\Models\TaxRate::find($rateId);
        $rateValue = $taxRate ? $taxRate->value : 0;

        if ($rateValue == 0) {
            return 0;
        }

        if ($status == 'after_taxes') {
            return ($amount / (1 - $rateValue / 100)) - $amount;
        }

        if ($status == 'before_taxes') {
            return $amount * ($rateValue / 100);
        }

        return 0;
    }
}
