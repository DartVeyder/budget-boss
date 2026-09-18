<?php

namespace App\Services\Finance\Act;

use App\Models\Act;
use App\Models\ActItem;
use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Models\Fop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DocumentGenerationService
{
    /**
     * Generate next act number in format: АКТ-YYYY/MM-01
     */
    public function generateActNumber(?int $userId = null): string
    {
        $userId = $userId ?? Auth::id();
        $datePrefix = Carbon::now()->format('Y/m');

        $latestAct = Act::where('user_id', $userId)
            ->where('act_number', 'LIKE', "АКТ-{$datePrefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        $lastSeq = 0;
        if ($latestAct && preg_match('/-(\d+)$/', $latestAct->act_number, $matches)) {
            $lastSeq = (int)$matches[1];
        }

        $nextSeq = str_pad($lastSeq + 1, 2, '0', STR_PAD_LEFT);

        return "АКТ-{$datePrefix}-{$nextSeq}";
    }

    /**
     * Generate next invoice number in format: РАХ-YYYY/MM-01
     */
    public function generateInvoiceNumber(?int $userId = null): string
    {
        $userId = $userId ?? Auth::id();
        $datePrefix = Carbon::now()->format('Y/m');

        $latestInvoice = FinanceInvoice::where('user_id', $userId)
            ->where('invoice_number', 'LIKE', "РАХ-{$datePrefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        $lastSeq = 0;
        if ($latestInvoice && preg_match('/-(\d+)$/', $latestInvoice->invoice_number, $matches)) {
            $lastSeq = (int)$matches[1];
        }

        $nextSeq = str_pad($lastSeq + 1, 2, '0', STR_PAD_LEFT);

        return "РАХ-{$datePrefix}-{$nextSeq}";
    }

    /**
     * Get formatted contractor/supplier details from FOP profile.
     */
    public function getFopDetails(?Fop $fop): array
    {
        if (!$fop) {
            return [
                'name' => 'Не вказано',
                'clean_name' => 'Не вказано',
                'name_with_fop' => 'Не вказано, ФОП',
                'fop_with_name' => 'ФОП Не вказано',
                'ipn' => '',
                'address' => '',
                'phone' => '',
                'iban' => '',
                'bank_name' => '',
                'tax_group' => '3 група',
                'tax_info' => 'Платник єдиного податку, 3 група, Не платник ПДВ',
                'full_requisites' => '',
            ];
        }

        $fop->loadMissing(['bill', 'fopGroup']);

        $name = trim($fop->name);
        $cleanName = trim(preg_replace('/^ФОП\s+|\s+ФОП$/ui', '', $name));
        $ipn = $fop->ipn ?: '';
        $address = $fop->address ?: '';
        $phone = $fop->phone ?: '';
        $iban = $fop->bill?->iban ?: '';
        $bankName = $fop->bill?->bank_name ?: ($fop->bill?->name ?: '');

        $groupName = $fop->fopGroup?->name ?: '3 група';
        $taxInfo = "Платник єдиного податку, {$groupName}, Не платник ПДВ";

        $parts = [];
        if ($name) $parts[] = "{$cleanName}, ФОП";
        if ($ipn) $parts[] = "ІПН {$ipn}";
        if ($address) $parts[] = $address;
        if ($iban) $parts[] = $iban;
        if ($bankName) $parts[] = "у банку {$bankName}";
        if ($phone) $parts[] = "тел.: {$phone}";
        $parts[] = $taxInfo;

        return [
            'fop' => $fop,
            'name' => $name,
            'clean_name' => $cleanName,
            'name_with_fop' => "{$cleanName}, ФОП",
            'fop_with_name' => "ФОП {$cleanName}",
            'ipn' => $ipn,
            'address' => $address,
            'phone' => $phone,
            'iban' => $iban,
            'bank_name' => $bankName,
            'tax_group' => $groupName,
            'tax_info' => $taxInfo,
            'full_requisites' => implode(', ', $parts),
        ];
    }

    /**
     * Get formatted customer/payer details.
     */
    public function getCustomerDetails(?Customer $customer, ?CustomerCounterparty $counterparty = null): array
    {
        if (!$customer && !$counterparty) {
            return [
                'name' => 'Не вказано',
                'clean_name' => 'Не вказано',
                'name_with_fop' => 'Не вказано',
                'fop_with_name' => 'Не вказано',
                'code' => '',
                'ipn' => '',
                'address' => '',
                'phone' => '',
                'iban' => '',
                'bank_name' => '',
                'director' => '',
                'tax_info' => 'Не платник ПДВ',
                'full_requisites' => '',
                'full_requisites_single_line' => '',
            ];
        }

        // If counterparty is specified, its legal name and IPN take priority for payer info
        $name = trim($counterparty?->name ?: ($customer?->name ?: ''));
        $cleanName = trim(preg_replace('/^ФОП\s+|\s+ФОП$/ui', '', $name));
        $isFop = ($counterparty !== null) || ($customer?->is_fop);

        $nameWithFop = $isFop ? "{$cleanName}, ФОП" : $name;
        $fopWithName = $isFop ? "ФОП {$cleanName}" : $name;

        $code = $counterparty?->ipn ?: ($customer?->ipn ?: ($customer?->edrpou ?: ''));
        $address = $customer?->address ?: '';
        $phone = $customer?->phone ?: '';
        $iban = $counterparty?->iban ?: ($customer?->iban ?: '');
        $bankName = $counterparty?->bank_name ?: ($customer?->bank_name ?: '');
        $director = $customer?->director ?: ($cleanName ?: $name);

        $parts = [];
        if ($address) $parts[] = "Адреса: {$address}";
        if ($iban) $parts[] = "р/р {$iban}";
        if ($bankName) $parts[] = "у банку {$bankName}";
        if ($code) $parts[] = "ІПН/ЄДРПОУ: {$code}";
        if ($phone) $parts[] = "тел.: {$phone}";

        $taxInfo = 'Не платник ПДВ';
        if ($customer?->tax_status === 'before_taxes' || $customer?->tax_status === 'after_taxes') {
            $taxInfo = 'Платник єдиного податку, Не платник ПДВ';
        }

        return [
            'name' => $nameWithFop,
            'clean_name' => $cleanName,
            'name_with_fop' => $nameWithFop,
            'fop_with_name' => $fopWithName,
            'code' => $code,
            'ipn' => $code,
            'address' => $address,
            'phone' => $phone,
            'iban' => $iban,
            'bank_name' => $bankName,
            'director' => $director,
            'tax_info' => $taxInfo,
            'full_requisites' => implode('<br>', $parts),
            'full_requisites_single_line' => implode(', ', $parts),
        ];
    }

    /**
     * Prepares default data for an Act created from an existing income transaction.
     */
    public function prepareFromTransaction(FinanceTransaction $transaction): array
    {
        $transaction->loadMissing(['customer.fop', 'counterparty', 'fop.bill', 'category']);

        $fop = $transaction->fop ?: ($transaction->customer?->fop ?: Fop::where('user_id', $transaction->user_id)->first());
        $customer = $transaction->customer;
        $counterparty = $transaction->counterparty;

        $serviceName = $transaction->comment ?: ($transaction->category?->name ?: 'Послуги згідно домовленості');
        $amount = (float)$transaction->amount;

        return [
            'act_number' => $this->generateActNumber($transaction->user_id),
            'act_date' => $transaction->accrual_date ? $transaction->accrual_date->toDateString() : ($transaction->created_at ? $transaction->created_at->toDateString() : Carbon::now()->toDateString()),
            'fop_id' => $fop?->id,
            'customer_id' => $customer?->id,
            'counterparty_id' => $counterparty?->id,
            'finance_transaction_id' => $transaction->id,
            'total_amount' => $amount,
            'currency_code' => $transaction->currency_code ?: '980',
            'status' => 'draft',
            'items' => [
                [
                    'name' => $serviceName,
                    'unit' => 'послуга',
                    'quantity' => 1,
                    'price' => $amount,
                    'amount' => $amount,
                ]
            ]
        ];
    }

    /**
     * Prepares default data for an Act created from an existing invoice.
     */
    public function prepareFromInvoice(FinanceInvoice $invoice): array
    {
        $invoice->loadMissing(['customer.fop', 'counterparty', 'fop.bill']);

        $fop = $invoice->fop ?: ($invoice->customer?->fop ?: Fop::where('user_id', $invoice->user_id)->first());
        $customer = $invoice->customer;
        $counterparty = $invoice->counterparty;

        $items = [];
        if (!empty($invoice->items_data) && is_array($invoice->items_data)) {
            $items = $invoice->items_data;
        } else {
            $items = [
                [
                    'name' => $invoice->comment ?: "Оплата згідно рахунку {$invoice->invoice_number}",
                    'unit' => 'послуга',
                    'quantity' => 1,
                    'price' => (float)$invoice->total,
                    'amount' => (float)$invoice->total,
                ]
            ];
        }

        return [
            'act_number' => $this->generateActNumber($invoice->user_id),
            'act_date' => Carbon::now()->toDateString(),
            'fop_id' => $fop?->id,
            'customer_id' => $customer?->id,
            'counterparty_id' => $counterparty?->id,
            'finance_invoice_id' => $invoice->id,
            'contract_number' => $invoice->contract_number,
            'contract_date' => $invoice->contract_date?->toDateString(),
            'total_amount' => (float)$invoice->total,
            'currency_code' => '980',
            'status' => 'draft',
            'items' => $items,
        ];
    }
}
