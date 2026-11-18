<?php

namespace App\Services\Finance\Fop;

use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\FinanceBill;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionMcc;
use App\Models\FinanceTransactionSource;
use App\Models\Fop;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\Api\Monobank\Monobank;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FopMonoSyncService
{
    protected TransactionExpensesService $expensesService;
    protected TransactionIncomeService $incomeService;

    public function __construct(
        TransactionExpensesService $expensesService,
        TransactionIncomeService $incomeService
    ) {
        $this->expensesService = $expensesService;
        $this->incomeService = $incomeService;
    }

    /**
     * Synchronize transactions for a FOP account from Monobank.
     *
     * @param Fop $fop
     * @param Carbon|string|int|null $from
     * @param Carbon|string|int|null $to
     * @return array
     */
    public function sync(Fop $fop, $from = null, $to = null): array
    {
        $userId = $fop->user_id;
        $setting = UserSetting::find($userId);

        if (!$setting || empty($setting->monobank_api_key)) {
            return [
                'success' => false,
                'message' => 'API-ключ Monobank не налаштовано в системі.',
            ];
        }

        $token = $setting->monobank_api_key;

        // Resolve Monobank account ID for FOP
        $accountId = $this->resolveFopAccountId($fop, $token);
        if (!$accountId) {
            return [
                'success' => false,
                'message' => 'Не вдалося визначити ID рахунку ФОП у Monobank. Перевірте IBAN або підключення в налаштуваннях.',
            ];
        }

        // Determine date range (Monobank supports up to 31 days)
        $range = $this->resolveDateRange($fop, $from, $to);
        $fromTs = $range['from'];
        $toTs = $range['to'];

        $statement = Monobank::getStatement($fromTs, $toTs, $token, $accountId);

        if ($statement === false) {
            return [
                'success' => false,
                'message' => 'Не вдалося отримати виписку з Monobank. Зачекайте 60 секунд (ліміт запитів) або перевірте API-токен.',
            ];
        }

        if (empty($statement)) {
            return [
                'success' => true,
                'imported' => 0,
                'updated' => 0,
                'total' => 0,
                'message' => 'Нових транзакцій не знайдено за вказаний період.',
            ];
        }

        $this->expensesService->setUserId($userId);
        $this->incomeService->setUserId($userId);

        return $this->processStatement($fop, $statement);
    }

    /**
     * Resolve Monobank Account ID for FOP (from FOP record, Bill, or auto-discovery).
     */
    public function resolveFopAccountId(Fop $fop, string $token): ?string
    {
        if (!empty($fop->monobank_account_id)) {
            return $fop->monobank_account_id;
        }

        if ($fop->bill && !empty($fop->bill->monobank_account_id)) {
            $fop->update(['monobank_account_id' => $fop->bill->monobank_account_id]);
            return $fop->bill->monobank_account_id;
        }

        // Auto-discover from client info
        $discovered = Monobank::findFopAccount($token, $fop->iban);
        if ($discovered && !empty($discovered['id'])) {
            $fop->update(['monobank_account_id' => $discovered['id']]);
            if ($fop->bill && empty($fop->bill->monobank_account_id)) {
                $fop->bill->update(['monobank_account_id' => $discovered['id']]);
            }
            return $discovered['id'];
        }

        return null;
    }

    /**
     * Determine optimal date range for statement retrieval.
     */
    protected function resolveDateRange(Fop $fop, $from = null, $to = null): array
    {
        $toTs = $to ? (is_numeric($to) ? (int)$to : strtotime((string)$to)) : time();

        if ($from) {
            $fromTs = is_numeric($from) ? (int)$from : strtotime((string)$from);
        } else {
            // Find latest transaction with mono_id for this FOP or bill
            $latestTx = FinanceTransaction::where('user_id', $fop->user_id)
                ->where(function ($q) use ($fop) {
                    $q->where('fop_id', $fop->id);
                    if ($fop->finance_bill_id) {
                        $q->orWhere('finance_bill_id', $fop->finance_bill_id);
                    }
                })
                ->whereNotNull('mono_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestTx) {
                // Look back 7 days before last transaction to catch any edits or refunds
                $fromTs = Carbon::parse($latestTx->created_at)->subDays(7)->timestamp;
            } else {
                $fromTs = Carbon::now()->subDays(30)->startOfDay()->timestamp;
            }
        }

        // Clamp to maximum 30 days
        $maxPast = $toTs - (30 * 86400);
        if ($fromTs < $maxPast) {
            $fromTs = $maxPast;
        }

        return [
            'from' => $fromTs,
            'to' => $toTs,
        ];
    }

    /**
     * Process statement items in chronological order.
     */
    protected function processStatement(Fop $fop, array $statement): array
    {
        // Process oldest to newest
        $items = array_reverse($statement);

        $userId = $fop->user_id;
        $billId = $fop->finance_bill_id;

        $importedCount = 0;
        $updatedCount = 0;

        $personalBill = $this->resolvePersonalMonobankBill($userId);
        $taxRates = $fop->fopGroup?->taxRates ?? collect();
        $taxRateIds = $taxRates->pluck('id')->toArray();

        foreach ($items as $item) {
            $monoId = $item['id'] ?? null;
            if (!$monoId) {
                continue;
            }

            $rawAmount = round(($item['amount'] ?? 0) / 100, 2);
            if ($rawAmount == 0) {
                continue;
            }

            $timeTs = $item['time'] ?? time();
            $itemDate = Carbon::createFromTimestamp($timeTs);
            $dateStr = $itemDate->format('Y-m-d H:i:s');

            $desc = $item['description'] ?? '';
            $comment = $item['comment'] ?? '';
            $edrpou = trim($item['counterEdrpou'] ?? '');
            $iban = trim($item['counterIban'] ?? '');
            $counterName = trim($item['counterName'] ?? '');
            $fullText = mb_strtolower("{$desc} {$comment} {$counterName}");

            $mcc = (int)($item['mcc'] ?? 0);
            if ($mcc > 0) {
                FinanceTransactionMcc::firstOrCreate(['code' => $mcc], ['code' => $mcc]);
            }

            // 1. Check exact match by mono_id
            $existing = FinanceTransaction::where('user_id', $userId)
                ->where('mono_id', $monoId)
                ->first();

            if ($existing) {
                // Ensure fop_id and bill_id are properly associated
                $needsSave = false;
                if (empty($existing->fop_id) && $rawAmount > 0) {
                    $existing->fop_id = $fop->id;
                    $needsSave = true;
                }
                if ($needsSave) {
                    $existing->save();
                }
                continue;
            }

            // 2. Check if transaction was previously created as a paired '_fop' record from personal sync
            $existingPair = FinanceTransaction::where('user_id', $userId)
                ->where('mono_id', $monoId . '_fop')
                ->first();

            if ($existingPair) {
                $existingPair->update([
                    'mono_id' => $monoId,
                    'fop_id' => $fop->id,
                ]);
                $updatedCount++;
                continue;
            }

            // 3. Check for existing manual/unsynced transaction with matching amount and date (+/- 24h)
            $manualMatch = FinanceTransaction::where('user_id', $userId)
                ->where('finance_bill_id', $billId)
                ->whereNull('mono_id')
                ->where('amount', $rawAmount)
                ->where(function ($q) use ($itemDate) {
                    $q->whereBetween('accrual_date', [$itemDate->copy()->subHours(24), $itemDate->copy()->addHours(24)])
                      ->orWhereBetween('created_at', [$itemDate->copy()->subHours(24), $itemDate->copy()->addHours(24)]);
                })
                ->first();

            if ($manualMatch) {
                $manualMatch->update([
                    'mono_id' => $monoId,
                    'fop_id' => $fop->id,
                ]);
                $updatedCount++;
                continue;
            }

            // 4. Check if this is a tax payment
            $taxInfo = $this->detectTaxPayment($fullText, $itemDate);
            if ($rawAmount < 0 && $taxInfo['is_tax']) {
                $this->createTaxPaymentTransaction($fop, $item, $rawAmount, $dateStr, $taxInfo);
                $importedCount++;
                continue;
            }

            // 5. Check if this is a transfer to own personal card
            $isSelfTransfer = $this->isSelfTransfer($fop, $edrpou, $counterName, $fullText);
            if ($rawAmount < 0 && $isSelfTransfer) {
                // Check if matching transfer transaction already exists within 120 seconds
                $existingTransfer = FinanceTransaction::where('user_id', $userId)
                    ->where('finance_bill_id', $billId)
                    ->where('transaction_type_id', 3)
                    ->where('amount', $rawAmount)
                    ->whereBetween('accrual_date', [$itemDate->copy()->subSeconds(120), $itemDate->copy()->addSeconds(120)])
                    ->first();

                if ($existingTransfer) {
                    $existingTransfer->update(['mono_id' => $monoId]);
                    $updatedCount++;
                    continue;
                }

                $this->createSelfTransferTransactions($fop, $personalBill, $item, $rawAmount, $dateStr, $monoId);
                $importedCount++;
                continue;
            }

            // 6. Customer Income or Refund
            if ($rawAmount > 0) {
                $descLower = mb_strtolower($fullText);
                $isRefund = str_contains($descLower, 'повернен') ||
                            str_contains($descLower, 'скасуван') ||
                            str_contains($descLower, 'refund') ||
                            str_contains($descLower, 'reversal') ||
                            str_contains($descLower, 'компенсац') ||
                            str_contains($descLower, 'відміна') ||
                            str_contains($descLower, 'chargeback');

                if ($isRefund) {
                    $this->createRefundTransaction($fop, $item, $rawAmount, $dateStr, $monoId);
                    $importedCount++;
                    continue;
                }

                $this->createIncomeTransaction($fop, $item, $rawAmount, $dateStr, $monoId, $taxRates, $taxRateIds);
                $importedCount++;
                continue;
            }

            // 7. General business expense / Bank fee
            $this->createExpenseTransaction($fop, $item, $rawAmount, $dateStr, $monoId);
            $importedCount++;
        }

        return [
            'success' => true,
            'imported' => $importedCount,
            'updated' => $updatedCount,
            'total' => count($items),
            'message' => "Імпортовано {$importedCount} нових транзакцій, оновлено {$updatedCount}.",
        ];
    }

    /**
     * Detect if transaction is a tax payment (Single tax, Military tax, ESV) and parse period.
     */
    protected function detectTaxPayment(string $fullText, Carbon $itemDate): array
    {
        $taxType = null;
        $isTax = false;

        if (str_contains($fullText, 'єдиний податок') || str_contains($fullText, 'єдиного податку') || str_contains($fullText, 'сплата єп')) {
            $isTax = true;
            $taxType = 'single_tax';
        } elseif (str_contains($fullText, 'військовий збір') || str_contains($fullText, 'військового збору') || str_contains($fullText, 'сплата вз')) {
            $isTax = true;
            $taxType = 'military_tax';
        } elseif (str_contains($fullText, 'єсв') || str_contains($fullText, 'єдиний внесок') || str_contains($fullText, 'єдиного внеску')) {
            $isTax = true;
            $taxType = 'esv';
        }

        if (!$isTax) {
            return ['is_tax' => false];
        }

        // Determine quarter
        $taxQuarter = null;
        if (preg_match('/([1-4])\s*(?:-й|й)?\s*кв(?:артал)?/ui', $fullText, $m)) {
            $taxQuarter = (int)$m[1];
        } elseif (str_contains($fullText, 'липень-вересень') || str_contains($fullText, 'лип-вер')) {
            $taxQuarter = 3;
        } elseif (str_contains($fullText, 'квітень-червень') || str_contains($fullText, 'квіт-черв')) {
            $taxQuarter = 2;
        } elseif (str_contains($fullText, 'січень-березень') || str_contains($fullText, 'січ-бер')) {
            $taxQuarter = 1;
        } elseif (str_contains($fullText, 'жовтень-грудень') || str_contains($fullText, 'жовт-груд')) {
            $taxQuarter = 4;
        }

        // Fallback quarter from payment date: taxes for previous quarter are usually paid in months 1, 4, 7, 10
        if (!$taxQuarter) {
            $month = $itemDate->month;
            if (in_array($month, [1, 2])) {
                $taxQuarter = 4;
            } elseif (in_array($month, [4, 5])) {
                $taxQuarter = 1;
            } elseif (in_array($month, [7, 8])) {
                $taxQuarter = 2;
            } elseif (in_array($month, [10, 11])) {
                $taxQuarter = 3;
            } else {
                $taxQuarter = (int)ceil($month / 3);
            }
        }

        // Determine year
        $taxYear = null;
        if (preg_match('/(20\d\d)\s*р/ui', $fullText, $m)) {
            $taxYear = (int)$m[1];
        } else {
            $taxYear = $itemDate->year;
            // If paying Q4 in Jan/Feb, year is previous
            if ($taxQuarter === 4 && in_array($itemDate->month, [1, 2])) {
                $taxYear -= 1;
            }
        }

        return [
            'is_tax' => true,
            'tax_type' => $taxType,
            'tax_quarter' => $taxQuarter,
            'tax_year' => $taxYear,
        ];
    }

    /**
     * Create tax payment transaction.
     */
    protected function createTaxPaymentTransaction(Fop $fop, array $item, float $rawAmount, string $dateStr, array $taxInfo): FinanceTransaction
    {
        $userId = $fop->user_id;
        $taxType = $taxInfo['tax_type'];

        // Resolve tax category
        $categoryName = match ($taxType) {
            'military_tax' => 'Військовий збір',
            'esv' => 'ЄСВ',
            default => 'Податок',
        };

        $category = FinanceTransactionCategory::where('user_id', $userId)
            ->where('name', $categoryName)
            ->first()
            ?? FinanceTransactionCategory::where('name', $categoryName)->first()
            ?? FinanceTransactionCategory::where('user_id', $userId)->where('name', 'like', "%податок%")->first()
            ?? FinanceTransactionCategory::find(87);

        $categoryId = $category?->id ?? 87;

        $desc = $item['description'] ?? '';
        $comment = $item['comment'] ?? $desc;
        $sourceName = $item['counterName'] ?? $desc;

        $source = FinanceTransactionSource::firstOrCreate(
            ['name' => mb_strtolower($sourceName)],
            ['name' => mb_strtolower($sourceName)]
        );

        $currencyData = $this->expensesService->getCurrency((int)$fop->finance_bill_id, $rawAmount);

        $balanceBill = $this->expensesService->getBalanceToBill($fop->finance_bill_id) + $rawAmount;
        $totalBalance = $this->expensesService->getTotalBalance() + $rawAmount;

        return FinanceTransaction::create(array_merge([
            'user_id' => $userId,
            'created_at' => $dateStr,
            'updated_at' => $dateStr,
            'accrual_date' => $dateStr,
            'transaction_category_id' => $categoryId,
            'customer_id' => null,
            'counterparty_id' => null,
            'finance_invoice_id' => null,
            'finance_bill_id' => $fop->finance_bill_id,
            'fop_id' => $fop->id,
            'tax_type' => $taxType,
            'tax_quarter' => $taxInfo['tax_quarter'],
            'tax_year' => $taxInfo['tax_year'],
            'source_name' => $sourceName,
            'transaction_source_id' => $source->id,
            'mcc_code' => $item['mcc'] ?? null,
            'amount' => $rawAmount,
            'tax_amount' => 0.0,
            'mono_id' => $item['id'],
            'balance' => $totalBalance,
            'balance_bill' => $balanceBill,
            'transaction_type_id' => 1, // Витрати
            'type' => 'expenses',
            'comment' => $comment,
            'is_balance' => 1,
        ], $currencyData));
    }

    /**
     * Check if transaction is transfer to own personal card.
     */
    protected function isSelfTransfer(Fop $fop, string $edrpou, string $counterName, string $fullText): bool
    {
        if (!empty($edrpou) && !empty($fop->ipn) && $edrpou === $fop->ipn) {
            return true;
        }

        $user = User::find($fop->user_id);
        if ($user && !empty($user->name)) {
            $userLastName = explode(' ', trim($user->name))[0] ?? '';
            if (mb_strlen($userLastName) >= 3 && str_contains(mb_strtolower($counterName), mb_strtolower($userLastName))) {
                return true;
            }
        }

        if (str_contains($fullText, 'переказ на картку') || str_contains($fullText, 'переказ на власну картку') || str_contains($fullText, 'виплата доходу фоп')) {
            return true;
        }

        return $this->expensesService->isFopSelfTransfer($fullText);
    }

    /**
     * Create paired self-transfer transactions.
     */
    protected function createSelfTransferTransactions(Fop $fop, ?FinanceBill $personalBill, array $item, float $rawAmount, string $dateStr, string $monoId): void
    {
        $userId = $fop->user_id;
        $fopBillId = $fop->finance_bill_id;
        $personalBillId = $personalBill?->id ?? 3;

        $transferCategory = FinanceTransactionCategory::where('user_id', $userId)
            ->where('name', 'Переказ')
            ->first()
            ?? FinanceTransactionCategory::where('name', 'Переказ')->first()
            ?? FinanceTransactionCategory::find(80)
            ?? FinanceTransactionCategory::find(1);

        $categoryId = $transferCategory ? $transferCategory->id : 1;
        $absAmount = abs($rawAmount);

        $sourceName = 'переказ на власну картку';
        $source = FinanceTransactionSource::firstOrCreate(
            ['name' => $sourceName],
            ['name' => $sourceName]
        );

        $balanceFopBill = $this->expensesService->getBalanceToBill($fopBillId) - $absAmount;
        $totalBalance = $this->expensesService->getTotalBalance();

        // 1. Expense from FOP bill
        $fopTxData = array_merge([
            'user_id' => $userId,
            'created_at' => $dateStr,
            'updated_at' => $dateStr,
            'accrual_date' => $dateStr,
            'transaction_category_id' => $categoryId,
            'customer_id' => null,
            'counterparty_id' => null,
            'finance_invoice_id' => null,
            'finance_bill_id' => $fopBillId,
            'fop_id' => $fop->id,
            'tax_type' => null,
            'tax_quarter' => null,
            'tax_year' => null,
            'source_name' => $sourceName,
            'transaction_source_id' => $source->id,
            'mcc_code' => $item['mcc'] ?? null,
            'amount' => -$absAmount,
            'tax_amount' => 0.0,
            'mono_id' => $monoId,
            'balance' => $totalBalance,
            'balance_bill' => $balanceFopBill,
            'transaction_type_id' => 3, // Переказ
            'type' => 'expenses',
            'comment' => 'Переказ на власну картку',
            'is_balance' => 0,
        ], $this->expensesService->getCurrency($fopBillId, -$absAmount));

        FinanceTransaction::create($fopTxData);

        // 2. Incoming to Personal Card (if different bill exists)
        if ($personalBillId && $personalBillId !== $fopBillId) {
            $balancePersonalBill = $this->expensesService->getBalanceToBill($personalBillId) + $absAmount;
            $personalTxData = array_merge([
                'user_id' => $userId,
                'created_at' => $dateStr,
                'updated_at' => $dateStr,
                'accrual_date' => $dateStr,
                'transaction_category_id' => $categoryId,
                'customer_id' => null,
                'counterparty_id' => null,
                'finance_invoice_id' => null,
                'finance_bill_id' => $personalBillId,
                'fop_id' => null,
                'tax_type' => null,
                'tax_quarter' => null,
                'tax_year' => null,
                'source_name' => 'переказ з рахунку фоп',
                'transaction_source_id' => $source->id,
                'mcc_code' => $item['mcc'] ?? null,
                'amount' => $absAmount,
                'tax_amount' => 0.0,
                'mono_id' => $monoId . '_card',
                'balance' => $totalBalance,
                'balance_bill' => $balancePersonalBill,
                'transaction_type_id' => 3, // Переказ
                'type' => 'income',
                'comment' => 'Переказ з рахунку ФОП',
                'is_balance' => 0,
            ], $this->expensesService->getCurrency($personalBillId, $absAmount));

            FinanceTransaction::create($personalTxData);
        }
    }

    /**
     * Create income transaction from customer payment.
     */
    protected function createIncomeTransaction(
        Fop $fop,
        array $item,
        float $rawAmount,
        string $dateStr,
        string $monoId,
        $taxRates,
        array $taxRateIds
    ): FinanceTransaction {
        $userId = $fop->user_id;
        $edrpou = trim($item['counterEdrpou'] ?? '');
        $iban = trim($item['counterIban'] ?? '');
        $counterName = trim($item['counterName'] ?? '');
        $desc = $item['description'] ?? '';
        $comment = $item['comment'] ?? '';
        $fullText = "{$desc} {$comment} {$counterName}";

        // 1. Match Counterparty & Customer
        $counterparty = null;
        if (!empty($edrpou)) {
            $counterparty = CustomerCounterparty::where('user_id', $userId)->where('ipn', $edrpou)->first();
        }
        if (!$counterparty && !empty($iban)) {
            $counterparty = CustomerCounterparty::where('user_id', $userId)->where('iban', $iban)->first();
        }
        if (!$counterparty && !empty($counterName)) {
            $counterparty = CustomerCounterparty::where('user_id', $userId)
                ->where('name', 'like', "%{$counterName}%")
                ->first();
        }

        $customerId = $counterparty?->customer_id;
        $counterpartyId = $counterparty?->id;

        // If counterparty not found, try matching Customer directly
        if (!$customerId && !empty($counterName)) {
            $customer = Customer::where('user_id', $userId)
                ->where('name', 'like', "%{$counterName}%")
                ->first();
            $customerId = $customer?->id;
        }

        // 2. Match Invoice
        $invoice = $this->matchInvoice($userId, $customerId, $counterpartyId, $rawAmount, $fullText);

        // 3. Category
        $categoryId = $fop->transaction_category_id
            ?? ($counterparty?->customer?->fop?->transaction_category_id)
            ?? 13; // Фріланс default

        // 4. Calculate Taxes
        $taxStatus = $fop->tax_status ?: 'before_taxes';
        $taxes = $this->incomeService->calculateTaxes($rawAmount, $taxStatus, $taxRateIds);

        // 5. Source
        $sourceName = $counterName ?: ($desc ?: 'дохід фоп');
        $source = FinanceTransactionSource::firstOrCreate(
            ['name' => mb_strtolower($sourceName)],
            ['name' => mb_strtolower($sourceName)]
        );

        $currencyData = $this->expensesService->getCurrency((int)$fop->finance_bill_id, $rawAmount);

        $balanceBill = $this->expensesService->getBalanceToBill($fop->finance_bill_id) + $rawAmount;
        $totalBalance = $this->expensesService->getTotalBalance() + $rawAmount;

        // Accrual date goes for the previous month (last day of the previous month)
        $accrualDate = $itemDate->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d 00:00:00');

        $tx = FinanceTransaction::create(array_merge([
            'user_id' => $userId,
            'created_at' => $dateStr,
            'updated_at' => $dateStr,
            'accrual_date' => $accrualDate,
            'transaction_category_id' => $categoryId,
            'customer_id' => $customerId,
            'counterparty_id' => $counterpartyId,
            'finance_invoice_id' => $invoice?->id,
            'finance_bill_id' => $fop->finance_bill_id,
            'fop_id' => $fop->id,
            'tax_type' => null,
            'tax_quarter' => null,
            'tax_year' => null,
            'source_name' => $sourceName,
            'transaction_source_id' => $source->id,
            'mcc_code' => $item['mcc'] ?? null,
            'amount' => $rawAmount,
            'tax_amount' => $taxes['total'] ?? 0.0,
            'mono_id' => $monoId,
            'balance' => $totalBalance,
            'balance_bill' => $balanceBill,
            'transaction_type_id' => 2, // Дохід
            'type' => 'income',
            'comment' => $comment ?: $desc,
            'is_balance' => 1,
        ], $currencyData));

        // Attach tax rates details
        if (!empty($taxes['details'])) {
            $tx->taxes()->sync($taxes['details']);
        }

        // Update invoice payment status and link Act if matched
        if ($invoice) {
            $this->incomeService->updateStatusInvoice($invoice->id);

            \App\Models\Act::where('finance_invoice_id', $invoice->id)
                ->whereNull('finance_transaction_id')
                ->update([
                    'finance_transaction_id' => $tx->id,
                    'status' => 'completed',
                ]);
        }

        return $tx;
    }

    /**
     * Match Invoice by invoice number in text or unpaid invoice with same amount.
     */
    protected function matchInvoice(int $userId, ?int $customerId, ?int $counterpartyId, float $rawAmount, string $fullText): ?FinanceInvoice
    {
        // 1. Try extracting invoice number from text (e.g. "рахунком № 20261105-01" or "INV-2024...")
        if (preg_match('/(?:рахунк(?:ом|у|а|ів)?|inv|invoice)\s*(?:№|no\.?|#)?\s*([A-Za-z0-9\/\-_]+)/ui', $fullText, $m)) {
            $invNum = trim($m[1]);
            $inv = FinanceInvoice::where('user_id', $userId)
                ->where(function ($q) use ($invNum) {
                    $q->where('invoice_number', $invNum)
                      ->orWhere('invoice_number', 'like', "%{$invNum}%");
                })
                ->first();

            if ($inv) {
                return $inv;
            }
        }

        // 2. Try finding unpaid invoice for this customer/counterparty with same amount
        if ($customerId) {
            $inv = FinanceInvoice::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->where('status', '!=', 'paid')
                ->where('total', $rawAmount)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($inv) {
                return $inv;
            }
        }

        return null;
    }

    /**
     * Create a refund / cancellation transaction for FOP (type 5, non-income).
     */
    protected function createRefundTransaction(Fop $fop, array $item, float $rawAmount, string $dateStr, string $monoId): FinanceTransaction
    {
        $userId = $fop->user_id;
        $desc = trim($item['description'] ?? '');
        $comment = trim($item['comment'] ?? '');
        $sourceName = $desc ?: 'Скасування';

        $source = FinanceTransactionSource::firstOrCreate(
            ['name' => mb_strtolower($sourceName)],
            ['name' => mb_strtolower($sourceName)]
        );

        $currencyData = $this->incomeService->getCurrency((int)$fop->finance_bill_id, $rawAmount);
        $balanceBill = $this->incomeService->getBalanceToBill($fop->finance_bill_id) + $rawAmount;
        $totalBalance = $this->incomeService->getTotalBalance() + $rawAmount;

        return FinanceTransaction::create(array_merge([
            'user_id' => $userId,
            'created_at' => $dateStr,
            'updated_at' => $dateStr,
            'accrual_date' => $dateStr,
            'transaction_category_id' => null,
            'customer_id' => null,
            'counterparty_id' => null,
            'finance_invoice_id' => null,
            'finance_bill_id' => $fop->finance_bill_id,
            'fop_id' => $fop->id,
            'tax_type' => null,
            'tax_quarter' => null,
            'tax_year' => null,
            'source_name' => $sourceName,
            'transaction_source_id' => $source->id,
            'mcc_code' => $item['mcc'] ?? null,
            'amount' => $rawAmount,
            'tax_amount' => 0.0,
            'mono_id' => $monoId,
            'balance' => $totalBalance,
            'balance_bill' => $balanceBill,
            'transaction_type_id' => 5, // Скасування
            'type' => 'income',
            'comment' => $comment ?: 'Скасування / Повернення коштів',
            'is_balance' => 1,
        ], $currencyData));
    }

    /**
     * Create general business expense or bank fee.
     */
    protected function createExpenseTransaction(Fop $fop, array $item, float $rawAmount, string $dateStr, string $monoId): FinanceTransaction
    {
        $userId = $fop->user_id;
        $desc = $item['description'] ?? '';
        $comment = $item['comment'] ?? $desc;
        $sourceName = $item['counterName'] ?? ($desc ?: 'витрата фоп');

        $source = FinanceTransactionSource::firstOrCreate(
            ['name' => mb_strtolower($sourceName)],
            ['name' => mb_strtolower($sourceName)]
        );

        // Category for fee or other
        $category = FinanceTransactionCategory::where('user_id', $userId)->where('name', 'like', '%комісія%')->first()
            ?? FinanceTransactionCategory::where('user_id', $userId)->where('name', 'Інше')->first()
            ?? FinanceTransactionCategory::find(11);

        $categoryId = $category?->id ?? 11;

        $currencyData = $this->expensesService->getCurrency((int)$fop->finance_bill_id, $rawAmount);

        $balanceBill = $this->expensesService->getBalanceToBill($fop->finance_bill_id) + $rawAmount;
        $totalBalance = $this->expensesService->getTotalBalance() + $rawAmount;

        return FinanceTransaction::create(array_merge([
            'user_id' => $userId,
            'created_at' => $dateStr,
            'updated_at' => $dateStr,
            'accrual_date' => $dateStr,
            'transaction_category_id' => $categoryId,
            'customer_id' => null,
            'counterparty_id' => null,
            'finance_invoice_id' => null,
            'finance_bill_id' => $fop->finance_bill_id,
            'fop_id' => $fop->id,
            'tax_type' => null,
            'tax_quarter' => null,
            'tax_year' => null,
            'source_name' => $sourceName,
            'transaction_source_id' => $source->id,
            'mcc_code' => $item['mcc'] ?? null,
            'amount' => $rawAmount,
            'tax_amount' => 0.0,
            'mono_id' => $monoId,
            'balance' => $totalBalance,
            'balance_bill' => $balanceBill,
            'transaction_type_id' => 1, // Витрати
            'type' => 'expenses',
            'comment' => $comment,
            'is_balance' => 1,
        ], $currencyData));
    }

    /**
     * Resolve personal Monobank bill.
     */
    protected function resolvePersonalMonobankBill(int $userId): ?FinanceBill
    {
        return FinanceBill::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('name', 'like', '%моно%')
                  ->orWhere('name', 'like', '%mono%')
                  ->orWhere('bank_name', 'like', '%моно%')
                  ->orWhere('bank_name', 'like', '%mono%');
            })
            ->where(function ($q) {
                $q->where('name', 'not like', '%фоп%')
                  ->where('name', 'not like', '%fop%');
            })
            ->first()
            ?? FinanceBill::where('user_id', $userId)->where('name', 'like', '%моно%')->first()
            ?? FinanceBill::where('user_id', $userId)->find(3);
    }
}

