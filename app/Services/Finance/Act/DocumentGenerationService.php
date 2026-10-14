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
     * Generate next act number based on user settings template.
     */
    public function generateActNumber(?int $userId = null, $date = null): string
    {
        $userId = $userId ?? Auth::id();
        $setting = \App\Models\UserSetting::where('user_id', $userId)->first();
        $template = $setting?->act_number_template ?: 'АКТ-{YYYY}/{MM}-{NUM}';

        return $this->generateDocumentNumber(Act::class, 'act_number', $template, $userId, 'АКТ', $date);
    }

    /**
     * Generate next invoice number based on user settings template.
     */
    public function generateInvoiceNumber(?int $userId = null, $date = null): string
    {
        $userId = $userId ?? Auth::id();
        $setting = \App\Models\UserSetting::where('user_id', $userId)->first();
        $template = $setting?->invoice_number_template ?: 'РАХ-{YYYY}/{MM}-{NUM}';

        return $this->generateDocumentNumber(FinanceInvoice::class, 'invoice_number', $template, $userId, 'РАХ', $date);
    }

    /**
     * Resolve document number based on custom template and existing records.
     *
     * Supported placeholders:
     * - {PREFIX} -> e.g. "АКТ" or "РАХ"
     * - {YYYY}   -> 2026 (4-digit year)
     * - {YY}     -> 26 (2-digit year)
     * - {MM}     -> 10 (2-digit month)
     * - {M}      -> 10 (1-12 month)
     * - {DD}     -> 13 (2-digit day)
     * - {NUM}    -> 01, 02 (default 2-digit sequence)
     * - {NUM:N}  -> sequence padded to N digits, e.g. {NUM:3} => 001
     */
    public function generateDocumentNumber(
        string $modelClass,
        string $numberField,
        string $template,
        ?int $userId = null,
        string $defaultPrefix = 'ДОК',
        $date = null
    ): string {
        $userId = $userId ?? Auth::id();
        $template = trim($template ?: "{$defaultPrefix}-{YYYY}/{MM}-{NUM}");

        // 1. Substitute prefix and date variables
        $docDate = $date ? Carbon::parse($date) : Carbon::now();
        $resolved = str_replace(
            ['{PREFIX}', '{YYYY}', '{YY}', '{MM}', '{M}', '{DD}'],
            [$defaultPrefix, $docDate->format('Y'), $docDate->format('y'), $docDate->format('m'), $docDate->format('n'), $docDate->format('d')],
            $template
        );

        // 2. Identify sequence placeholder: {NUM} or {NUM:X}
        $padLength = 2;
        $placeholder = '{NUM}';
        if (preg_match('/\{NUM(?::(\d+))?\}/', $resolved, $matches)) {
            $placeholder = $matches[0];
            $padLength = !empty($matches[1]) ? (int)$matches[1] : 2;
        }

        // 3. Split static prefix and suffix around placeholder
        $parts = explode($placeholder, $resolved, 2);
        $prefixPart = $parts[0] ?? '';
        $suffixPart = $parts[1] ?? '';

        // 4. Query highest matching sequence
        $query = $modelClass::where('user_id', $userId);
        if ($prefixPart !== '') {
            $query->where($numberField, 'LIKE', "{$prefixPart}%");
        }
        $latest = $query->orderBy('id', 'desc')->first();

        $lastSeq = 0;
        if ($latest) {
            $currentVal = (string)$latest->$numberField;
            if ($prefixPart !== '' && str_starts_with($currentVal, $prefixPart)) {
                $rest = substr($currentVal, strlen($prefixPart));
                if ($suffixPart !== '' && str_ends_with($rest, $suffixPart)) {
                    $rest = substr($rest, 0, -strlen($suffixPart));
                }
                if (preg_match('/^(\d+)/', $rest, $m)) {
                    $lastSeq = (int)$m[1];
                }
            }

            if ($lastSeq === 0 && preg_match('/(\d+)(?:' . preg_quote($suffixPart, '/') . ')?$/', $currentVal, $m)) {
                $lastSeq = (int)$m[1];
            }
        }

        $nextSeq = str_pad((string)($lastSeq + 1), $padLength, '0', STR_PAD_LEFT);

        return $prefixPart . $nextSeq . $suffixPart;
    }

    /**
     * Preview what a document number will look like for a template.
     */
    public function previewDocumentNumber(string $template, string $defaultPrefix = 'АКТ'): string
    {
        $now = Carbon::now();
        $resolved = str_replace(
            ['{PREFIX}', '{YYYY}', '{YY}', '{MM}', '{M}', '{DD}'],
            [$defaultPrefix, $now->format('Y'), $now->format('y'), $now->format('m'), $now->format('n'), $now->format('d')],
            trim($template)
        );

        $padLength = 2;
        if (preg_match('/\{NUM(?::(\d+))?\}/', $resolved, $matches)) {
            $placeholder = $matches[0];
            $padLength = !empty($matches[1]) ? (int)$matches[1] : 2;
            $sampleSeq = str_pad('1', $padLength, '0', STR_PAD_LEFT);
            return str_replace($placeholder, $sampleSeq, $resolved);
        }

        return $resolved;
    }

    /**
     * Format a party's name into a short representation for document filenames.
     * E.g. "Медвідь Дмитро Степанович" -> "ФОП Медвідь Д"
     *      "ФОП Мисак Оксана Степанівна" -> "ФОП Мисак О"
     *      "ТОВ \"Твайс ЮА\"" -> "ТОВ Твайс ЮА"
     *      "Twice" -> "Twice"
     */
    public function formatPartyShortName(?string $name, bool $isFop = true): string
    {
        if (empty($name)) {
            return '';
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $name = trim($name, " \t\n\r\0\x0B\"'«»");

        $hasFopPrefix = (bool)preg_match('/^(?:ФОП|FOP)\b/ui', $name);
        $hasFopSuffix = (bool)preg_match('/\b(?:ФОП|FOP)$/ui', $name);
        $isFop = $isFop || $hasFopPrefix || $hasFopSuffix;

        $clean = trim(preg_replace('/^(?:ФОП|FOP)\s+|\s+(?:ФОП|FOP)$/ui', '', $name));
        $clean = trim($clean, " \t\n\r\0\x0B\"'«»");

        if (empty($clean)) {
            return $isFop ? 'ФОП' : '';
        }

        // If it's a corporate entity (ТОВ, ПП, АТ, TOV, etc.), preserve company name
        if (preg_match('/^(?:ТОВ|ПП|ПрАТ|АТ|ВАТ|TOV|LLC)\b/ui', $clean)) {
            return preg_replace('/["\'«»]/u', '', $clean);
        }

        $words = explode(' ', $clean);

        if ($isFop) {
            if (count($words) >= 2) {
                $lastName = $words[0];
                $firstInitial = mb_substr(rtrim($words[1], '.'), 0, 1, 'UTF-8');
                return "ФОП {$lastName} {$firstInitial}";
            }
            return "ФОП {$words[0]}";
        }

        if (count($words) >= 2 && !preg_match('/[0-9]/', $clean)) {
            $lastName = $words[0];
            $firstInitial = mb_substr(rtrim($words[1], '.'), 0, 1, 'UTF-8');
            return "{$lastName} {$firstInitial}";
        }

        return $clean;
    }

    /**
     * Format a date into Ukrainian words: e.g. "18 вересня 2026 р"
     */
    public function formatUkrainianDateInWords($date): string
    {
        if (!$date) {
            $date = Carbon::now();
        } elseif (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $months = [
            1 => 'січня', 2 => 'лютого', 3 => 'березня', 4 => 'квітня',
            5 => 'травня', 6 => 'червня', 7 => 'липня', 8 => 'серпня',
            9 => 'вересня', 10 => 'жовтня', 11 => 'листопада', 12 => 'грудня',
        ];

        $day = $date->format('j');
        $month = $months[(int)$date->format('n')] ?? '';
        $year = $date->format('Y');

        return "{$day} {$month} {$year} р";
    }

    /**
     * Generate standard human-readable filename for an Act.
     * Example: "Акт надання послуг ФОП Медвідь Д і ФОП Мисак О № 20260918-01 від 18 вересня 2026 р.pdf"
     */
    public function generateActFilename(Act $act, string $extension = 'pdf'): string
    {
        $act->loadMissing(['fop', 'customer', 'counterparty']);

        $fopShort = $this->formatPartyShortName($act->fop?->name, true);

        $customerName = $act->counterparty?->name ?: ($act->customer?->name ?: '');
        $isCustomerFop = ($act->counterparty !== null) || ($act->customer?->is_fop ?? true);
        $customerShort = $this->formatPartyShortName($customerName, $isCustomerFop);

        $parties = '';
        if ($fopShort !== '' && $customerShort !== '') {
            $parties = "{$fopShort} і {$customerShort}";
        } elseif ($fopShort !== '') {
            $parties = $fopShort;
        } elseif ($customerShort !== '') {
            $parties = $customerShort;
        }

        $cleanNumber = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', trim((string)$act->act_number));
        $numberPart = $cleanNumber !== '' ? "№ {$cleanNumber}" : '';

        $dateFormatted = $this->formatUkrainianDateInWords($act->act_date);
        $datePart = "від {$dateFormatted}";

        $parts = array_filter(['Акт надання послуг', $parties, $numberPart, $datePart]);
        $baseName = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)));

        return $baseName . '.' . ltrim($extension, '.');
    }

    /**
     * Generate standard human-readable filename for an Invoice.
     * Example: "Рахунок на оплату ФОП Медвідь Д і ФОП Мисак О № 20260918-01 від 18 вересня 2026 р.pdf"
     */
    public function generateInvoiceFilename(FinanceInvoice $invoice, string $extension = 'pdf'): string
    {
        $invoice->loadMissing(['fop', 'customer', 'counterparty', 'act.fop']);

        $fop = $invoice->fop ?: ($invoice->customer?->fop ?: ($invoice->act?->fop ?: Fop::where('user_id', $invoice->user_id)->first()));
        $fopShort = $this->formatPartyShortName($fop?->name, true);

        $customerName = $invoice->counterparty?->name ?: ($invoice->customer?->name ?: '');
        $isCustomerFop = ($invoice->counterparty !== null) || ($invoice->customer?->is_fop ?? true);
        $customerShort = $this->formatPartyShortName($customerName, $isCustomerFop);

        $parties = '';
        if ($fopShort !== '' && $customerShort !== '') {
            $parties = "{$fopShort} і {$customerShort}";
        } elseif ($fopShort !== '') {
            $parties = $fopShort;
        } elseif ($customerShort !== '') {
            $parties = $customerShort;
        }

        $cleanNumber = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', trim((string)$invoice->invoice_number));
        $numberPart = $cleanNumber !== '' ? "№ {$cleanNumber}" : '';

        $invoiceDate = $invoice->act?->act_date ?: ($invoice->invoice_date ?: $invoice->created_at);
        $dateFormatted = $this->formatUkrainianDateInWords($invoiceDate);
        $datePart = "від {$dateFormatted}";

        $parts = array_filter(['Рахунок на оплату', $parties, $numberPart, $datePart]);
        $baseName = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)));

        return $baseName . '.' . ltrim($extension, '.');
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
        $iban = $fop->iban ?: ($fop->bill?->iban ?: '');
        $bankName = $fop->bank_name ?: ($fop->bill?->bank_name ?: ($fop->bill?->name ?: ''));

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
            'contract_number' => $fop?->contract_number,
            'contract_date' => $fop?->contract_date?->toDateString(),
            'contract_attachment' => $fop?->attachment?->first(),
            'full_requisites' => implode(', ', $parts),
        ];
    }

    /**
     * Get formatted customer/payer details.
     */
    public function getCustomerDetails(?Customer $customer, ?CustomerCounterparty $counterparty = null, ?Act $act = null): array
    {
        if (!$customer && !$counterparty) {
            return [
                'name' => 'Не вказано',
                'clean_name' => 'Не вказано',
                'name_with_fop' => 'Не вказано',
                'fop_with_name' => 'Не вказано',
                'code' => '',
                'ipn' => '',
                'address' => $act?->customer_address ?: '',
                'phone' => $act?->customer_phone ?: '',
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
        $address = $act?->customer_address ?: ($counterparty?->address ?: ($customer?->address ?: ''));
        $phone = $act?->customer_phone ?: ($counterparty?->phone ?: ($customer?->phone ?: ''));
        $iban = $counterparty?->iban ?: ($customer?->iban ?: '');
        $bankName = $counterparty?->bank_name ?: ($customer?->bank_name ?: '');
        $director = $customer?->director ?: ($cleanName ?: $name);

        $parts = [];
        if ($address) $parts[] = "Адреса: {$address}";
        if ($iban) $parts[] = "р/р {$iban}";
        if ($bankName) $parts[] = "у банку {$bankName}";
        if ($code) $parts[] = "ІПН/ЄДРПОУ: {$code}";
        if ($phone) $parts[] = "тел.: {$phone}";

        $taxGroup = $act?->customer_tax_group 
            ?: ($counterparty?->tax_group ?: ($customer?->tax_group ?: null));

        $isSingleTax = $act?->customer_is_single_tax !== null
            ? (bool)$act->customer_is_single_tax
            : ($counterparty?->is_single_tax !== null ? (bool)$counterparty->is_single_tax : ($customer?->is_single_tax ?? true));

        $isVatPayer = $act?->customer_is_vat_payer !== null
            ? (bool)$act->customer_is_vat_payer
            : ($counterparty?->is_vat_payer !== null ? (bool)$counterparty->is_vat_payer : ($customer?->is_vat_payer ?? false));

        $taxInfo = $act?->customer_tax_info ?: $this->formatTaxInfo($isSingleTax, $taxGroup, $isVatPayer);

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
            'tax_group' => $taxGroup,
            'is_single_tax' => $isSingleTax,
            'is_vat_payer' => $isVatPayer,
            'tax_info' => $taxInfo,
            'contract_number' => $counterparty?->contract_number ?: null,
            'contract_date' => $counterparty?->contract_date?->toDateString() ?: null,
            'contract_attachment' => $counterparty?->attachment?->first() ?: null,
            'full_requisites' => implode('<br>', $parts),
            'full_requisites_single_line' => implode(', ', $parts),
        ];
    }

    /**
     * Format tax status info string for parties (e.g. Платник єдиного податку, 2 група, Не платник ПДВ).
     */
    public function formatTaxInfo(bool $isSingleTax = true, ?string $taxGroup = null, bool $isVatPayer = false): string
    {
        $parts = [];
        if ($isSingleTax) {
            $parts[] = 'Платник єдиного податку';
            if (!empty($taxGroup)) {
                $cleanGroup = trim(str_ireplace(['група', 'группа'], '', $taxGroup));
                $parts[] = is_numeric($cleanGroup) ? "{$cleanGroup} група" : $taxGroup;
            }
        }
        $parts[] = $isVatPayer ? 'Платник ПДВ' : 'Не платник ПДВ';

        return implode(', ', $parts);
    }

    /**
     * Prepares default data for an Act created from an existing income transaction.
     */
    public function prepareFromTransaction(FinanceTransaction $transaction): array
    {
        $transaction->loadMissing(['customer.fop', 'counterparty', 'fop.bill', 'category', 'attachment']);

        $fop = $transaction->fop ?: ($transaction->customer?->fop ?: Fop::where('user_id', $transaction->user_id)->first());
        $customer = $transaction->customer;
        $counterparty = $transaction->counterparty;

        $serviceName = $transaction->comment ?: ($transaction->category?->name ?: 'Послуги згідно домовленості');
        $amount = (float)$transaction->amount;

        if (!$counterparty && $customer) {
            $customer->loadMissing(['counterparties' => fn ($q) => $q->active()]);
            if ($customer->counterparties->count() === 1) {
                $counterparty = $customer->counterparties->first();
            }
        }

        $customerAddress = $counterparty?->address ?: ($customer?->address ?: '');
        $customerPhone = $counterparty?->phone ?: ($customer?->phone ?: '');
        $customerTaxGroup = $counterparty?->tax_group ?: ($customer?->tax_group ?: null);
        $customerIsSingleTax = $counterparty ? (bool)$counterparty->is_single_tax : ($customer ? (bool)$customer->is_single_tax : true);
        $customerIsVatPayer = $counterparty ? (bool)$counterparty->is_vat_payer : ($customer ? (bool)$customer->is_vat_payer : false);

        // Classify attachments from transaction (Acts vs Invoices)
        $actAttachmentIds = [];
        $invoiceAttachmentIds = [];
        if ($transaction->relationLoaded('attachment')) {
            foreach ($transaction->attachment as $att) {
                $n = mb_strtolower($att->original_name ?? '');
                if (str_contains($n, 'акт') || str_contains($n, 'akt')) {
                    $actAttachmentIds[] = $att->id;
                } elseif (str_contains($n, 'рах') || str_contains($n, 'rakh') || str_contains($n, 'inv')) {
                    $invoiceAttachmentIds[] = $att->id;
                }
            }
            if (empty($actAttachmentIds) && $transaction->attachment->isNotEmpty()) {
                $actAttachmentIds = $transaction->attachment->pluck('id')->toArray();
            }
        }

        $status = !empty($actAttachmentIds) ? 'signed' : 'draft';

        return [
            'act_number' => $this->generateActNumber($transaction->user_id),
            'act_date' => $transaction->accrual_date ? Carbon::parse($transaction->accrual_date)->toDateString() : ($transaction->created_at ? Carbon::parse($transaction->created_at)->toDateString() : Carbon::now()->toDateString()),
            'fop_id' => $fop?->id,
            'customer_id' => $customer?->id,
            'counterparty_id' => $counterparty?->id,
            'customer_address' => $customerAddress,
            'customer_phone' => $customerPhone,
            'customer_tax_group' => $customerTaxGroup,
            'customer_is_single_tax' => $customerIsSingleTax,
            'customer_is_vat_payer' => $customerIsVatPayer,
            'contract_number' => $counterparty?->contract_number ?: $fop?->contract_number,
            'contract_date' => $counterparty?->contract_date ? $counterparty->contract_date->toDateString() : ($fop?->contract_date?->toDateString()),
            'finance_transaction_id' => $transaction->id,
            'total_amount' => $amount,
            'currency_code' => $transaction->currency_code ?: '980',
            'status' => $status,
            'attachments' => $actAttachmentIds,
            'invoice_attachments' => $invoiceAttachmentIds,
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

        if (!$counterparty && $customer) {
            $customer->loadMissing(['counterparties' => fn ($q) => $q->active()]);
            if ($customer->counterparties->count() === 1) {
                $counterparty = $customer->counterparties->first();
            }
        }

        $customerAddress = $counterparty?->address ?: ($customer?->address ?: '');
        $customerPhone = $counterparty?->phone ?: ($customer?->phone ?: '');
        $customerTaxGroup = $counterparty?->tax_group ?: ($customer?->tax_group ?: null);
        $customerIsSingleTax = $counterparty ? (bool)$counterparty->is_single_tax : ($customer ? (bool)$customer->is_single_tax : true);
        $customerIsVatPayer = $counterparty ? (bool)$counterparty->is_vat_payer : ($customer ? (bool)$customer->is_vat_payer : false);

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
            'act_date' => $invoice->invoice_date ? $invoice->invoice_date->toDateString() : ($invoice->created_at ? $invoice->created_at->toDateString() : Carbon::now()->toDateString()),
            'fop_id' => $fop?->id,
            'customer_id' => $customer?->id,
            'counterparty_id' => $counterparty?->id,
            'customer_address' => $customerAddress,
            'customer_phone' => $customerPhone,
            'customer_tax_group' => $customerTaxGroup,
            'customer_is_single_tax' => $customerIsSingleTax,
            'customer_is_vat_payer' => $customerIsVatPayer,
            'finance_invoice_id' => $invoice->id,
            'contract_number' => $invoice->contract_number ?: ($counterparty?->contract_number ?: $fop?->contract_number),
            'contract_date' => $invoice->contract_date ? $invoice->contract_date->toDateString() : ($counterparty?->contract_date ? $counterparty->contract_date->toDateString() : ($fop?->contract_date?->toDateString())),
            'total_amount' => (float)$invoice->total,
            'currency_code' => '980',
            'status' => 'draft',
            'items' => $items,
        ];
    }

    /**
     * Parse contract number and date from text or filename.
     */
    public function parseContractDetails(string $text): array
    {
        $result = ['number' => null, 'date' => null];

        // 1. Try to find number: e.g. "Договір надання послуг: МД18092026-01", "Договір № 12/2026", "Контракт № 55"
        if (preg_match('/(?:договір(?:\s+надання\s+послуг)?|контракт)\s*(?::|№)?\s*([A-Za-zА-Яа-яІіЇїЄє0-9\/\-_]+)/ui', $text, $nm)) {
            $candidate = trim($nm[1]);
            if (mb_strtolower($candidate) !== 'від' && mb_strtolower($candidate) !== 'про') {
                $result['number'] = $candidate;
            }
        } elseif (preg_match('/№\s*([A-Za-zА-Яа-яІіЇїЄє0-9\/\-_]+)/ui', $text, $nm)) {
            $result['number'] = trim($nm[1]);
        }

        // 2. Try to find date: "від 18 вересня 2026", "від 18.09.2026", "2026-09-18"
        $months = [
            'січня' => 1, 'лютого' => 2, 'березня' => 3, 'квітня' => 4,
            'травня' => 5, 'червня' => 6, 'липня' => 7, 'серпня' => 8,
            'вересня' => 9, 'жовтня' => 10, 'листопада' => 11, 'грудня' => 12,
        ];

        foreach ($months as $mName => $mNum) {
            if (preg_match('/(\d{1,2})\s+' . $mName . '\s+(\d{4})/ui', $text, $dm)) {
                $result['date'] = sprintf('%04d-%02d-%02d', $dm[2], $mNum, $dm[1]);
                break;
            }
        }

        if (!$result['date']) {
            if (preg_match('/(?:\bвід\s+)?(\d{1,2})[.\/](\d{1,2})[.\/](\d{4})/ui', $text, $dm)) {
                $result['date'] = sprintf('%04d-%02d-%02d', $dm[3], $dm[2], $dm[1]);
            } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $text, $dm)) {
                $result['date'] = $dm[0];
            }
        }

        return $result;
    }

    /**
     * Extract contract details from an Orchid attachment or uploaded file.
     */
    public function extractContractDetailsFromAttachment($attachment): array
    {
        $result = ['number' => null, 'date' => null];
        if (!$attachment) {
            return $result;
        }

        // 1. Try from original filename
        $filename = is_object($attachment) ? ($attachment->original_name ?? $attachment->name ?? '') : (string)$attachment;
        if ($filename) {
            $fromName = $this->parseContractDetails($filename);
            if ($fromName['number']) $result['number'] = $fromName['number'];
            if ($fromName['date']) $result['date'] = $fromName['date'];
        }

        // 2. If it's a DOCX file and details are missing, inspect internal XML
        $filePath = null;
        if (is_object($attachment) && method_exists($attachment, 'physicalPath')) {
            $filePath = $attachment->physicalPath();
        } elseif (is_object($attachment) && !empty($attachment->path)) {
            $disk = $attachment->disk ?? 'public';
            try {
                $storagePath = \Illuminate\Support\Facades\Storage::disk($disk)->path($attachment->path . $attachment->name . '.' . $attachment->extension);
                if (file_exists($storagePath)) {
                    $filePath = $storagePath;
                }
            } catch (\Throwable) {
                // Ignore path resolution errors
            }
        }

        if ($filePath && file_exists($filePath) && (!$result['number'] || !$result['date'])) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if ($ext === 'docx') {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($xml) {
                        $fromDocx = $this->parseContractDetails(strip_tags($xml));
                        if (!$result['number'] && $fromDocx['number']) $result['number'] = $fromDocx['number'];
                        if (!$result['date'] && $fromDocx['date']) $result['date'] = $fromDocx['date'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Batch import and link Acts and Invoices from existing income transactions with attachments.
     */
    public function importFromTransactions(?int $userId = null): array
    {
        $userId = $userId ?: \Illuminate\Support\Facades\Auth::id();
        $transactions = FinanceTransaction::where('user_id', $userId)
            ->where('type', 'income')
            ->where(function($q) {
                $q->whereNull('transaction_type_id')
                  ->orWhere('transaction_type_id', '!=', 3);
            })
            ->whereDoesntHave('act')
            ->has('attachment')
            ->with(['attachment', 'customer.fop', 'counterparty', 'fop'])
            ->get();

        $imported = 0;
        foreach ($transactions as $tx) {
            $prepared = $this->prepareFromTransaction($tx);

            // Separate Act files and Invoice files
            $actFiles = [];
            $invoiceFiles = [];
            foreach ($tx->attachment as $att) {
                $n = mb_strtolower($att->original_name ?? '');
                if (str_contains($n, 'акт') || str_contains($n, 'akt')) {
                    $actFiles[] = $att;
                } elseif (str_contains($n, 'рах') || str_contains($n, 'rakh') || str_contains($n, 'inv')) {
                    $invoiceFiles[] = $att;
                }
            }

            // Extract act number and date from filename if present
            if (!empty($actFiles)) {
                $parsed = $this->parseContractDetails($actFiles[0]->original_name);
                if (!empty($parsed['number'])) {
                    $prepared['act_number'] = $parsed['number'];
                }
                if (!empty($parsed['date'])) {
                    $prepared['act_date'] = $parsed['date'];
                }
            }

            $items = $prepared['items'] ?? [];
            unset($prepared['items'], $prepared['attachments'], $prepared['invoice_attachments']);

            $prepared['user_id'] = $userId;
            $prepared['status'] = !empty($actFiles) ? 'signed' : 'draft';

            $act = Act::create($prepared);
            foreach ($items as $item) {
                $act->items()->create($item);
            }

            if (!empty($actFiles)) {
                $act->attachment()->sync(collect($actFiles)->pluck('id'));
            } elseif ($tx->attachment->isNotEmpty()) {
                $act->attachment()->sync($tx->attachment->pluck('id'));
            }

            // Create Invoice for this transaction
            $invoice = new FinanceInvoice();
            $invoice->user_id = $userId;
            $invoice->customer_id = $act->customer_id;
            $invoice->fop_id = $act->fop_id;
            $invoice->counterparty_id = $act->counterparty_id;
            $invoice->invoice_number = $this->generateInvoiceNumber($userId);
            $invoice->invoice_date = $act->act_date;
            $invoice->due_date = $act->act_date;
            $invoice->contract_number = $act->contract_number;
            $invoice->contract_date = $act->contract_date;
            $invoice->total = $act->total_amount;
            $invoice->comment = $items[0]['name'] ?? 'Оплата послуг';
            $invoice->items_data = $items;
            $invoice->finance_currency_id = 1;
            $invoice->status = 'paid';
            $invoice->amount_paid = $act->total_amount;
            $invoice->save();

            if (!empty($invoiceFiles)) {
                $invoice->attachment()->sync(collect($invoiceFiles)->pluck('id'));
            }

            $act->finance_invoice_id = $invoice->id;
            $act->save();

            $tx->finance_invoice_id = $invoice->id;
            $tx->save();

            $imported++;
        }

        return ['imported' => $imported];
    }
}
