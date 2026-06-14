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
                    $transaction['finance_bill_id'] = $customer->fop->finance_bill_id ?? null;
                }

                if (empty($transaction['transaction_category_id'])) {
                    $transaction['transaction_category_id'] = $customer->fop->transaction_category_id ?? null;
                }
                
                $taxStatus = $request->input('tax_status');
                if (empty($taxStatus) || $taxStatus == 'without_taxes') {
                    $taxStatus = $customer->fop->tax_status ?? 'without_taxes';
                }

                $taxRateIds = $request->input('tax_rates', []);
                if (empty($taxRateIds)) {
                    $taxRateIds = $customer->fop?->fopGroup?->taxRates?->pluck('id')->toArray() ?? [];
                }
            }
        } else {
            $taxStatus = $request->input('tax_status');
            $taxRateIds = $request->input('tax_rates', []);
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
        
        $taxes = $this->calculateTaxes($transaction['currency_amount'], $taxStatus, $taxRateIds ?? []);
        $transaction['tax_amount'] = $taxes['total'];
        $transaction['tax_details'] = $taxes['details']; // We will use this in the save method

        $transaction['user_id'] = $this->getUserId();

        return $transaction;


    }

    public function calculateTaxes(float $amount, string|null $status = 'without_taxes', array $rateIds = []): array
    {
        $result = [
            'total' => 0,
            'details' => []
        ];

        if ($status == 'without_taxes' || empty($rateIds)) {
            return $result;
        }

        $taxRates = \App\Models\TaxRate::whereIn('id', $rateIds)->get();

        foreach ($taxRates as $taxRate) {
            $rateValue = $taxRate->value ?? 0;
            if ($rateValue == 0) continue;

            $taxAmount = 0;
            if ($status == 'after_taxes') {
                $taxAmount = ($amount / (1 - $rateValue / 100)) - $amount;
            } elseif ($status == 'before_taxes') {
                $taxAmount = $amount * ($rateValue / 100);
            }

            $result['total'] += $taxAmount;
            $result['details'][$taxRate->id] = ['amount' => $taxAmount];
        }

        return $result;
    }
}
