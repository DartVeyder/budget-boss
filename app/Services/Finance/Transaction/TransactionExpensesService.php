<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionMcc;
use App\Models\FinanceTransactionSource;
use App\Models\Fop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionExpensesService extends TransactionsService
{
    protected string $type = 'expenses';
    public function __construct()
    {
        $this->setType($this->type);
        parent::__construct();
    }

    public function createInsertData(Request $request): array
    {
        $transaction = $request->input('transaction');
        if (!$transaction['created_at']) {
            unset($transaction['created_at']);
        }
        $transaction['balance'] = $this->getTotalBalance() - $transaction['amount'];
        $transaction['balance_bill'] = $this->getBalanceToBill($transaction['finance_bill_id']) - $transaction['amount'];
        $transaction['amount'] = $this->getAmountNegative($transaction['amount']);
        $transaction['user_id'] = $this->getUserId();

        $transaction['fop_id'] = !empty($transaction['fop_id']) ? (int)$transaction['fop_id'] : null;
        $transaction['tax_type'] = !empty($transaction['tax_type']) ? $transaction['tax_type'] : null;
        $transaction['tax_quarter'] = !empty($transaction['tax_quarter']) ? (int)$transaction['tax_quarter'] : null;
        $transaction['tax_year'] = !empty($transaction['tax_year']) ? (int)$transaction['tax_year'] : null;

        $transaction = array_merge($transaction, $this->getCurrency($transaction['finance_bill_id'], $transaction['amount']));

        return $transaction;
    }

    public function createInsertDataMono($data): array
    {
        $transactions = [];
        $data = array_reverse($data);

        // Find personal Monobank bill (excluding FOP bills)
        $personalBill = FinanceBill::where('user_id', $this->getUserId())
            ->where(function($q) {
                $q->where('name', 'like', '%моно%')
                  ->orWhere('name', 'like', '%mono%')
                  ->orWhere('bank_name', 'like', '%моно%')
                  ->orWhere('bank_name', 'like', '%mono%');
            })
            ->where(function($q) {
                $q->where('name', 'not like', '%фоп%')
                  ->where('name', 'not like', '%fop%');
            })
            ->first()
            ?? FinanceBill::where('user_id', $this->getUserId())
                ->where('name', 'like', '%моно%')
                ->first()
            ?? FinanceBill::where('user_id', $this->getUserId())->find(3)
            ?? FinanceBill::where('user_id', $this->getUserId())->first();

        $billId = $personalBill ? $personalBill->id : 3;

        // Find FOP bill and FOP model for paired transfer
        $fop = Fop::where('user_id', $this->getUserId())
            ->where('is_active', true)
            ->first()
            ?? Fop::where('user_id', $this->getUserId())->first();

        $fopBill = $fop?->bill
            ?? FinanceBill::where('user_id', $this->getUserId())
                ->where(function($q) {
                    $q->where('name', 'like', '%фоп%')
                      ->orWhere('name', 'like', '%fop%');
                })->first();

        $fopBillId = $fopBill?->id;

        // Prevent duplicates by checking existing mono_ids for this user
        $monoIds = array_filter(array_column($data, 'id'));
        $checkIds = $monoIds;
        foreach ($monoIds as $mId) {
            $checkIds[] = $mId . '_fop';
        }

        // Clean up any soft-deleted records for incoming mono_ids so re-syncing works seamlessly
        if (!empty($checkIds)) {
            FinanceTransaction::onlyTrashed()
                ->where('user_id', $this->getUserId())
                ->whereIn('mono_id', $checkIds)
                ->forceDelete();
        }

        $existingMonoIds = !empty($checkIds)
            ? FinanceTransaction::where('user_id', $this->getUserId())
                ->whereIn('mono_id', $checkIds)
                ->pluck('mono_id')
                ->flip()
                ->toArray()
            : [];

        $balance_bill = $this->getBalanceToBill($billId);
        $balance_fop_bill = $fopBillId ? $this->getBalanceToBill($fopBillId) : 0;
        $balance = $this->getTotalBalance();

        foreach ($data as $item) {
            if (empty($item['id']) || isset($existingMonoIds[$item['id']])) {
                continue;
            }

            if (empty($item['amount'])) {
                continue;
            }

            $rawAmount = $this->getAmount($item['amount']);
            $isPositive = $item['amount'] > 0;

            $description = $item['description'] ?? '';
            $source_name = str_replace("\n", " ", mb_strtolower($description));
            $transaction_source = FinanceTransactionSource::firstOrCreate(
                ['name' => $source_name],
                ['name' => $source_name]
            );

            $date = $this->getDate($item['time']);

            $mcc = (int)($item['mcc'] ?? 0);
            if ($mcc > 0) {
                FinanceTransactionMcc::firstOrCreate(['code' => $mcc], ['code' => $mcc]);
            }

            $descLower = mb_strtolower($description);

            // Check if transaction is a transfer between own FOP and personal accounts
            $isSelfTransfer = $this->isFopSelfTransfer($description);

            if ($isSelfTransfer) {
                $typeId = 3; // Переказ
                $isBalance = 0; // Внутрішні перекази між власними рахунками не змінюють сукупний баланс

                $transferCategory = FinanceTransactionCategory::where('user_id', $this->getUserId())
                    ->where('name', 'Переказ')
                    ->first()
                    ?? FinanceTransactionCategory::where('name', 'Переказ')->first()
                    ?? FinanceTransactionCategory::where('user_id', $this->getUserId())
                        ->where('name', 'like', 'Переказ%')
                        ->first()
                    ?? FinanceTransactionCategory::where('name', 'like', 'Переказ%')->first()
                    ?? FinanceTransactionCategory::find(1);
                $categoryId = $transferCategory ? $transferCategory->id : 1;

                if ($isPositive) {
                    // Кошти надійшли на особистий рахунок з рахунку ФОП
                    $balance_bill += $rawAmount;
                    $comment = 'Переказ з рахунку ФОП';

                    $transaction = [
                        'user_id' => $this->getUserId(),
                        'created_at' => $date,
                        'updated_at' => $date,
                        'accrual_date' => $date,
                        'transaction_category_id' => $categoryId,
                        'customer_id' => null,
                        'counterparty_id' => null,
                        'finance_invoice_id' => null,
                        'finance_bill_id' => $billId,
                        'fop_id' => null,
                        'tax_type' => null,
                        'tax_quarter' => null,
                        'tax_year' => null,
                        'source_name' => $source_name,
                        'transaction_source_id' => $transaction_source->id,
                        'mcc_code' => $mcc ?: null,
                        'amount' => $rawAmount,
                        'tax_amount' => 0.0,
                        'mono_id' => $item['id'],
                        'balance' => $balance,
                        'balance_bill' => $balance_bill,
                        'transaction_type_id' => $typeId,
                        'type' => 'income',
                        'comment' => $comment,
                        'is_balance' => $isBalance,
                    ];

                    $transaction = array_merge(
                        $transaction,
                        $this->getCurrency($transaction['finance_bill_id'], $transaction['amount'])
                    );
                    $transactions[] = $transaction;

                    // Парна транзакція списання з рахунку ФОП
                    if ($fopBillId && $fopBillId !== $billId) {
                        $balance_fop_bill -= $rawAmount;
                        $fopSourceName = 'переказ на особисту картку';
                        $fopSource = FinanceTransactionSource::firstOrCreate(
                            ['name' => $fopSourceName],
                            ['name' => $fopSourceName]
                        );

                        $fopTransaction = [
                            'user_id' => $this->getUserId(),
                            'created_at' => $date,
                            'updated_at' => $date,
                            'accrual_date' => $date,
                            'transaction_category_id' => $categoryId,
                            'customer_id' => null,
                            'counterparty_id' => null,
                            'finance_invoice_id' => null,
                            'finance_bill_id' => $fopBillId,
                            'fop_id' => $fop?->id,
                            'tax_type' => null,
                            'tax_quarter' => null,
                            'tax_year' => null,
                            'source_name' => $fopSourceName,
                            'transaction_source_id' => $fopSource->id,
                            'mcc_code' => $mcc ?: null,
                            'amount' => -$rawAmount,
                            'tax_amount' => 0.0,
                            'mono_id' => $item['id'] . '_fop',
                            'balance' => $balance,
                            'balance_bill' => $balance_fop_bill,
                            'transaction_type_id' => $typeId,
                            'type' => 'expenses',
                            'comment' => 'Переказ на особисту картку',
                            'is_balance' => $isBalance,
                        ];

                        $fopTransaction = array_merge(
                            $fopTransaction,
                            $this->getCurrency($fopTransaction['finance_bill_id'], $fopTransaction['amount'])
                        );
                        $transactions[] = $fopTransaction;
                    }
                } else {
                    // Кошти переказані з особистої картки на рахунок ФОП
                    $balance_bill += $rawAmount; // $rawAmount від'ємний
                    $comment = 'Переказ на рахунок ФОП';

                    $transaction = [
                        'user_id' => $this->getUserId(),
                        'created_at' => $date,
                        'updated_at' => $date,
                        'accrual_date' => $date,
                        'transaction_category_id' => $categoryId,
                        'customer_id' => null,
                        'counterparty_id' => null,
                        'finance_invoice_id' => null,
                        'finance_bill_id' => $billId,
                        'fop_id' => null,
                        'tax_type' => null,
                        'tax_quarter' => null,
                        'tax_year' => null,
                        'source_name' => $source_name,
                        'transaction_source_id' => $transaction_source->id,
                        'mcc_code' => $mcc ?: null,
                        'amount' => $rawAmount,
                        'tax_amount' => 0.0,
                        'mono_id' => $item['id'],
                        'balance' => $balance,
                        'balance_bill' => $balance_bill,
                        'transaction_type_id' => $typeId,
                        'type' => 'expenses',
                        'comment' => $comment,
                        'is_balance' => $isBalance,
                    ];

                    $transaction = array_merge(
                        $transaction,
                        $this->getCurrency($transaction['finance_bill_id'], $transaction['amount'])
                    );
                    $transactions[] = $transaction;

                    // Парна транзакція надходження на рахунок ФОП
                    if ($fopBillId && $fopBillId !== $billId) {
                        $balance_fop_bill += abs($rawAmount);
                        $fopSourceName = 'поповнення з особистої картки';
                        $fopSource = FinanceTransactionSource::firstOrCreate(
                            ['name' => $fopSourceName],
                            ['name' => $fopSourceName]
                        );

                        $fopTransaction = [
                            'user_id' => $this->getUserId(),
                            'created_at' => $date,
                            'updated_at' => $date,
                            'accrual_date' => $date,
                            'transaction_category_id' => $categoryId,
                            'customer_id' => null,
                            'counterparty_id' => null,
                            'finance_invoice_id' => null,
                            'finance_bill_id' => $fopBillId,
                            'fop_id' => $fop?->id,
                            'tax_type' => null,
                            'tax_quarter' => null,
                            'tax_year' => null,
                            'source_name' => $fopSourceName,
                            'transaction_source_id' => $fopSource->id,
                            'mcc_code' => $mcc ?: null,
                            'amount' => abs($rawAmount),
                            'tax_amount' => 0.0,
                            'mono_id' => $item['id'] . '_fop',
                            'balance' => $balance,
                            'balance_bill' => $balance_fop_bill,
                            'transaction_type_id' => $typeId,
                            'type' => 'income',
                            'comment' => 'Поповнення з особистої картки',
                            'is_balance' => $isBalance,
                        ];

                        $fopTransaction = array_merge(
                            $fopTransaction,
                            $this->getCurrency($fopTransaction['finance_bill_id'], $fopTransaction['amount'])
                        );
                        $transactions[] = $fopTransaction;
                    }
                }

                $existingMonoIds[$item['id']] = true;
                if ($fopBillId) {
                    $existingMonoIds[$item['id'] . '_fop'] = true;
                }
                continue;
            }

            // Accurately adjust running balances for standard income/expense
            $balance += $rawAmount;
            $balance_bill += $rawAmount;

            $isRefund = $isPositive && (
                str_contains($descLower, 'повернен') ||
                str_contains($descLower, 'скасуван') ||
                str_contains($descLower, 'refund') ||
                str_contains($descLower, 'reversal') ||
                str_contains($descLower, 'компенсац') ||
                str_contains($descLower, 'відміна') ||
                str_contains($descLower, 'chargeback') ||
                $this->isMerchantMcc($mcc)
            );

            $categoryId = $mcc > 0 ? $this->getCategoryWithMcc($mcc) : null;

            if ($isPositive) {
                $type = 'income';
                $typeId = 2; // Дохід

                if ($isRefund) {
                    $comment = 'Повернення коштів';
                    if (!$categoryId) {
                        $refundCategory = FinanceTransactionCategory::firstOrCreate(
                            [
                                'user_id' => $this->getUserId(),
                                'name' => 'Повернення коштів',
                            ],
                            [
                                'transaction_type_id' => 2,
                            ]
                        );
                        $categoryId = $refundCategory->id;
                    }
                } else {
                    $comment = null;
                }
            } else {
                $type = 'expenses';
                $typeId = 1; // Витрати
                $comment = null;
            }

            $transaction = [
                'user_id' => $this->getUserId(),
                'created_at' => $date,
                'updated_at' => $date,
                'accrual_date' => $date,
                'transaction_category_id' => $categoryId,
                'customer_id' => null,
                'counterparty_id' => null,
                'finance_invoice_id' => null,
                'finance_bill_id' => $billId,
                'fop_id' => null,
                'tax_type' => null,
                'tax_quarter' => null,
                'tax_year' => null,
                'source_name' => $source_name,
                'transaction_source_id' => $transaction_source->id,
                'mcc_code' => $mcc ?: null,
                'amount' => $rawAmount,
                'tax_amount' => 0.0,
                'mono_id' => $item['id'],
                'balance' => $balance,
                'balance_bill' => $balance_bill,
                'transaction_type_id' => $typeId,
                'type' => $type,
                'comment' => $comment,
                'is_balance' => 1,
            ];

            $transaction = array_merge(
                $transaction,
                $this->getCurrency($transaction['finance_bill_id'], $transaction['amount'])
            );

            $transactions[] = $transaction;
            $existingMonoIds[$item['id']] = true;
        }

        return $transactions;
    }

    public function isFopSelfTransfer(string $description): bool
    {
        if (empty($description)) {
            return false;
        }

        $descLower = mb_strtolower(trim($description));

        $phrases = [
            'з гривневого рахунку фоп',
            'з валютного рахунку фоп',
            'з рахунку фоп',
            'зі свого рахунку фоп',
            'з власного рахунку фоп',
            'переказ з рахунку фоп',
            'переказ зі свого рахунку фоп',
            'переказ з власного рахунку фоп',
            'перерахування доходу фоп',
            'виплата доходу фоп',
            'дохід фоп',
            'на гривневий рахунок фоп',
            'на валютний рахунок фоп',
            'на рахунок фоп',
            'на власний рахунок фоп',
            'на свій рахунок фоп',
            'поповнення рахунку фоп',
            'переказ на рахунок фоп',
            'переказ на власну карту',
            'переказ на власну картку',
            'переказ з власної карти',
            'переказ з власної картки',
            'переказ власних коштів',
        ];

        foreach ($phrases as $phrase) {
            if (str_contains($descLower, $phrase)) {
                return true;
            }
        }

        $fops = Fop::where('user_id', $this->getUserId())->get();
        foreach ($fops as $fop) {
            if (!empty($fop->iban) && str_contains($descLower, mb_strtolower($fop->iban))) {
                return true;
            }

            if (!empty($fop->name)) {
                $parts = array_filter(explode(' ', mb_strtolower(trim($fop->name))));
                $lastName = $parts[0] ?? null;
                if ($lastName && mb_strlen($lastName) >= 3 && str_contains($descLower, 'фоп') && str_contains($descLower, $lastName)) {
                    return true;
                }
            }
        }

        $user = User::find($this->getUserId());
        if ($user && !empty($user->name)) {
            $userParts = array_filter(explode(' ', mb_strtolower(trim($user->name))));
            $userLastName = $userParts[0] ?? null;
            if ($userLastName && mb_strlen($userLastName) >= 3 && str_contains($descLower, 'фоп') && str_contains($descLower, $userLastName)) {
                return true;
            }
        }

        return false;
    }

    public function isMerchantMcc(int $mcc): bool
    {
        if ($mcc <= 0) {
            return false;
        }

        // Non-merchant MCCs (P2P transfers, cash withdrawals, financial operations)
        $nonMerchantMccs = [
            4829, // Wire Transfer (P2P)
            6010, // Manual Cash
            6011, // ATM Cash
            6012, // Financial Institutions
            6536, // MoneySend Domestic
            6538, // MoneySend Funding
            6540, // POI Funding Transactions
        ];

        return !in_array($mcc, $nonMerchantMccs, true);
    }

    private function getDate($time): string
    {
        $carbonDate = Carbon::createFromTimestamp($time);
        return $carbonDate->format('Y-m-d H:i:s');
    }

    private function getAmount(int $amount): float
    {
        return $amount / 100;
    }

    private function getCategoryWithMcc(int $code): int|null
    {
        $mcc = FinanceTransactionMcc::where('code', $code)->first();
        if (is_null($mcc)) {
            return null;
        }
        return $mcc->categories->first()->id ?? null;
    }
}

