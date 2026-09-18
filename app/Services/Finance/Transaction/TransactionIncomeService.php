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
    public function createInsertData($request): array
    {
        $transaction = $request->input('transaction', []);

        if (empty($transaction['created_at'])) {
            unset($transaction['created_at']);
        }

        if (empty($transaction['accrual_date'])) {
            unset($transaction['accrual_date']);
        }

        if (!empty($transaction['finance_invoice_id'])) {
            $invoice = FinanceInvoice::find($transaction['finance_invoice_id']);
            if ($invoice) {
                $transaction['accrual_date'] = $invoice->created_at;
            }
        }

        $taxStatus = $request->input('tax_status');
        $taxRateIds = $request->input('tax_rates', []);

        if (!empty($transaction['customer_id'])) {
            $customer = \App\Models\Customer::find($transaction['customer_id']);
            if ($customer) {
                if (empty($transaction['finance_bill_id'])) {
                    $transaction['finance_bill_id'] = $customer->fop->finance_bill_id ?? null;
                }

                if (empty($transaction['transaction_category_id'])) {
                    $transaction['transaction_category_id'] = $customer->fop->transaction_category_id ?? null;
                }

                if (empty($taxStatus) || $taxStatus === 'without_taxes') {
                    $taxStatus = $customer->fop->tax_status ?? 'without_taxes';
                }

                if (empty($taxRateIds)) {
                    $taxRateIds = $customer->fop?->fopGroup?->taxRates?->pluck('id')->toArray() ?? [];
                }
            }
        }

        // Завжди приводимо до масиву
        if (!is_array($taxRateIds)) {
            $taxRateIds = !empty($taxRateIds) ? [(int)$taxRateIds] : [];
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

        $transaction = array_merge($transaction, $this->getCurrency((int)$transaction['finance_bill_id'], (float)$transaction['amount']));
        $transaction['balance'] = $this->getTotalBalance() + (float)$transaction['amount'];
        $transaction['balance_bill'] = $this->getBalanceToBill((int)$transaction['finance_bill_id']) + (float)$transaction['amount'];

        $taxes = $this->calculateTaxes((float)$transaction['currency_amount'], $taxStatus, $taxRateIds);
        $transaction['tax_amount'] = $taxes['total'];
        $transaction['tax_details'] = $taxes['details']; // We will use this in the save method

        $fopId = $transaction['fop_id'] ?? null;
        if (empty($fopId) && !empty($transaction['customer_id'])) {
            $customer = \App\Models\Customer::find($transaction['customer_id']);
            if ($customer && $customer->fop_id) {
                $fopId = $customer->fop_id;
            }
        }
        if (empty($fopId) && !empty($transaction['finance_bill_id'])) {
            $fopMatch = \App\Models\Fop::where('finance_bill_id', $transaction['finance_bill_id'])->where('user_id', $this->getUserId())->first();
            if ($fopMatch) {
                $fopId = $fopMatch->id;
            }
        }
        $transaction['fop_id'] = $fopId;

        if (isset($transaction['counterparty_id'])) {
            $transaction['counterparty_id'] = !empty($transaction['counterparty_id']) ? (int)$transaction['counterparty_id'] : null;
        }

        $transaction['user_id'] = $this->getUserId();

        return $transaction;
    }

    public function calculateTaxes(float $amount, ?string $status = 'without_taxes', array|int|string|null $rateIds = []): array
    {
        $result = [
            'total' => 0,
            'details' => []
        ];

        if (!is_array($rateIds)) {
            $rateIds = !empty($rateIds) ? [(int)$rateIds] : [];
        }

        if (empty($status) || $status === 'without_taxes' || empty($rateIds)) {
            return $result;
        }

        $taxRates = \App\Models\TaxRate::whereIn('id', $rateIds)->get();
        if ($taxRates->isEmpty()) {
            return $result;
        }

        if ($status === 'after_taxes') {
            $totalRate = $taxRates->sum('value');
            if ($totalRate > 0 && $totalRate < 100) {
                $grossAmount = $amount / (1 - ($totalRate / 100));
                foreach ($taxRates as $taxRate) {
                    $rateValue = (float)($taxRate->value ?? 0);
                    if ($rateValue <= 0) continue;
                    $taxAmount = round($grossAmount * ($rateValue / 100), 2);
                    $result['total'] += $taxAmount;
                    $result['details'][$taxRate->id] = ['amount' => $taxAmount];
                }
            }
        } elseif ($status === 'before_taxes') {
            foreach ($taxRates as $taxRate) {
                $rateValue = (float)($taxRate->value ?? 0);
                if ($rateValue <= 0) continue;
                $taxAmount = round($amount * ($rateValue / 100), 2);
                $result['total'] += $taxAmount;
                $result['details'][$taxRate->id] = ['amount' => $taxAmount];
            }
        }

        return $result;
    }
}
