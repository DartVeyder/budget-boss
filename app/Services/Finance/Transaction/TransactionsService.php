<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Services\Currency\Currency;
use App\Services\Finance\Crypto\Binance\BinanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionsService
{
    protected int $userId;
    protected array $userIds = [];
    protected string $type;
    protected ?int $billId = null;

    public function __construct()
    {
        $this->setUserId(Auth::id() ?? 0);
    }

    public function setBillId(?int $billId): self
    {
        $this->billId = $billId;
        return $this;
    }

    public function getBillId(): ?int
    {
        return $this->billId;
    }

    public function list($filter = null, $start = null, $end = null, ?int $billId = null, ?int $categoryId = null){
        $query = FinanceTransaction::whereIn('user_id', $this->getUserIds());
        if ($filter && !empty($_GET['created_at']['start'])) {
            $query = $query->filters($filter);
        } elseif ($start && $end) {
            $query = $query->whereBetween('created_at', [$start, $end]);
        } elseif ($filter) {
            $query = $query->filters($filter);
        }

        $effectiveBillId = $billId ?? $this->billId;
        if ($effectiveBillId) {
            $query->where('finance_bill_id', $effectiveBillId);
        }

        if ($categoryId) {
            $query->where('transaction_category_id', $categoryId);
        }

        return $query->orderBy('created_at', 'DESC')->paginate();
    }

    public function getTotalAmount(  string $value, bool $isCurrency = false, $filter = null, $start = null,$end = null, ?int $billId = null ):float|string
    {
        $query = FinanceTransaction::whereIn('user_id', $this->getUserIds())->where('is_balance' ,1) ;
        if ($filter && !empty($_GET['created_at']['start'])) {
            $query = $query->filters($filter);
        } else {
            $query = $query->whereBetween('created_at', [$start, $end]);
        }

        $effectiveBillId = $billId ?? $this->billId;
        if ($effectiveBillId) {
            $query->where('finance_bill_id', $effectiveBillId);
        }

        if($isCurrency){
            return  Currency::convertValueToCurrency($query->sum($value));
        }

        return (float) $query->sum($value);
    }
    public function getTotalBalance():float{
        return (float)$this->getTotalAmountInUsd() + BinanceService::getBalanceUAH();
    }
    public function getTotalAmountInUsd() :float{
        return (float) FinanceTransaction::leftJoin('finance_currencies', 'finance_transactions.finance_currency_id', '=', 'finance_currencies.id')
            ->select(DB::raw('SUM(finance_transactions.amount * finance_currencies.value) as total_amount'))
            ->where('finance_transactions.is_balance' ,1)
            ->whereIn('finance_transactions.user_id', $this->getUserIds())
            ->value('total_amount'); // Отримання значення суми

    }
    public function getSum(  string $value, bool $isCurrency = false, $filter = null, $start = null,$end = null, ?int $billId = null ):float|string
    {
        if ($filter && !empty($_GET['created_at']['start'])) {
            $query = $this->query()->filters($filter);
        } else {
            $query = $this->query()->whereBetween('created_at', [$start, $end]);
        }

        $effectiveBillId = $billId ?? $this->billId;
        if ($effectiveBillId && !$this->billId) {
            $query->where('finance_bill_id', $effectiveBillId);
        }

        if($isCurrency){
            return  Currency::convertValueToCurrency($query->sum($value));
        }

        return (float) $query->sum($value);
    }

    public function getBalanceToBill($billId) :float
    {
        return FinanceTransaction::where('finance_bill_id', $billId)->sum('amount');
    }
    private function formatChartData($collection, $name)
    {
        $total = $collection->sum('total_amount');
        $total = $total > 0 ? $total : 1;

        $labels = [];
        $values = [];
        $items = [];
        
        $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'];
        $colorIndex = 0;

        foreach ($collection as $item) {
            $percent = round(($item->total_amount / $total) * 100, 2);
            $formattedAmount = \App\Services\Currency\Currency::convertValueToCurrency($item->total_amount);
            $labels[] = $item->name . ' - ' . $percent . '% (' . $formattedAmount . ')';
            $values[] = $percent;
            
            $items[] = [
                'id' => $item->id ?? null,
                'name' => $item->name,
                'percent' => $percent,
                'amount' => $formattedAmount,
                'color' => $colors[$colorIndex % count($colors)]
            ];
            $colorIndex++;
        }

        return [[
            'name' =>  $name,
            'labels' => $labels,
            'values' => $values,
            'items'  => $items
        ]];
    }

    public function chartPieSource($start, $end, $name='', ?int $billId = null){
        $effectiveBillId = $billId ?? $this->billId;
        $q = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->whereIn('finance_transactions.user_id', $this->getUserIds())
            ->where('is_balance' ,1)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $this->getType());

        if ($effectiveBillId) {
            $q->where('finance_transactions.finance_bill_id', $effectiveBillId);
        }

        $collection = $q->join('finance_transaction_sources', 'finance_transactions.transaction_source_id', '=', 'finance_transaction_sources.id')
            ->select('finance_transaction_sources.id', 'finance_transaction_sources.name', DB::raw('ABS(SUM(finance_transactions.currency_amount)) as total_amount'))
            ->groupBy('finance_transaction_sources.id', 'finance_transaction_sources.name')
            ->orderBy('total_amount', 'desc')
            ->get();
        return $this->formatChartData($collection, $name);
    }
    public function chartPieCategory($start, $end, $name='', ?int $billId = null){
        $effectiveBillId = $billId ?? $this->billId;
        $q = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->whereIn('finance_transactions.user_id', $this->getUserIds())
            ->where('is_balance' ,1)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $this->getType());

        if ($effectiveBillId) {
            $q->where('finance_transactions.finance_bill_id', $effectiveBillId);
        }

        $collection = $q->join('finance_transaction_categories', 'finance_transactions.transaction_category_id', '=', 'finance_transaction_categories.id')
            ->select('finance_transaction_categories.id', 'finance_transaction_categories.name', DB::raw('ABS(SUM(finance_transactions.currency_amount)) as total_amount'))
            ->groupBy('finance_transaction_categories.id', 'finance_transaction_categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();
        return $this->formatChartData($collection, $name);
    }

    public function chartPieBill($start, $end,$name = '', ?int $billId = null){
        $effectiveBillId = $billId ?? $this->billId;
        $q = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->whereIn('finance_transactions.user_id', $this->getUserIds())
            ->where('is_balance' ,1)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $this->getType());

        if ($effectiveBillId) {
            $q->where('finance_transactions.finance_bill_id', $effectiveBillId);
        }

        $collection = $q->join('finance_bills', 'finance_transactions.finance_bill_id', '=', 'finance_bills.id')
            ->select('finance_bills.id', 'finance_bills.name',DB::raw('ABS(SUM(finance_transactions.currency_amount))  as total_amount'))
            ->groupBy('finance_bills.id', 'finance_bills.name')
            ->orderBy('total_amount', 'desc')
            ->get();
        return $this->formatChartData($collection, $name);
    }

    public function chartPieCustomer($start, $end, $name = '', ?int $billId = null){
        $effectiveBillId = $billId ?? $this->billId;
        $q = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->whereIn('finance_transactions.user_id', $this->getUserIds())
            ->whereNull('finance_transactions.deleted_at')
            ->where('is_balance' ,1);

        if ($effectiveBillId) {
            $q->where('finance_transactions.finance_bill_id', $effectiveBillId);
        }

        $collection = $q->join('customers', 'finance_transactions.customer_id', '=', 'customers.id')
            ->select('customers.id', 'customers.name',DB::raw('ABS(SUM(finance_transactions.currency_amount))  as total_amount'))
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('total_amount', 'desc')
            ->get();
        return $this->formatChartData($collection, $name);
    }
    public function chartBarBalance(?string $name = null, ?string $start = null, ?string $end = null, ?string $dateColumn = null, string $value = 'currency_amount', ?int $billId = null) :array
    {
        $effectiveBillId = $billId ?? $this->billId;
        $q = FinanceTransaction::whereIn('user_id', $this->getUserIds())->where('is_balance' ,1);
        if ($effectiveBillId) {
            $q->where('finance_bill_id', $effectiveBillId);
        }
        return $q->SumByMonths($value,  $start, $end, $dateColumn)->toChart($name);
    }

    public function chartBar(?string $name = null, ?string $start = null, ?string $end = null, ?string $dateColumn = null, string $value = 'currency_amount', ?int $billId = null) :array
    {
        $effectiveBillId = $billId ?? $this->billId;
        $q = $this->query()->where('is_balance' ,1);
        if ($effectiveBillId && !$this->billId) {
            $q->where('finance_bill_id', $effectiveBillId);
        }
        return $q->SumByMonths($value,  $start, $end, $dateColumn)->toChart($name);
    }

    public function chartBarComparison(string $nameCurrent = 'Current', string $namePrev = 'Previous', ?string $start = null, ?string $end = null, ?string $dateColumn = null, string $value = 'currency_amount', ?int $billId = null) :array
    {
        $currentStart = Carbon::parse($start);
        $currentEnd = Carbon::parse($end);
        
        $prevStart = $currentStart->copy()->subYear();
        $prevEnd = $currentEnd->copy()->subYear();

        $effectiveBillId = $billId ?? $this->billId;
        $qCurrent = $this->query()->where('is_balance' ,1);
        $qPrev = $this->query()->where('is_balance' ,1);
        if ($effectiveBillId && !$this->billId) {
            $qCurrent->where('finance_bill_id', $effectiveBillId);
            $qPrev->where('finance_bill_id', $effectiveBillId);
        }

        $currentData = $qCurrent->SumByMonths($value,  $currentStart, $currentEnd, $dateColumn)->toChart($nameCurrent);
        $prevData = $qPrev->SumByMonths($value,  $prevStart, $prevEnd, $dateColumn)->toChart($namePrev);

        $mappedLabels = array_map(function($label) {
            return Carbon::createFromFormat('Y-m-d', $label . '-01')->translatedFormat('M');
        }, $currentData['labels']);

        $currentData['labels'] = $mappedLabels;
        $prevData['labels'] = $mappedLabels;

        return [$currentData, $prevData];
    }

    public function chartCapital(?string $name = null, ?string $start = null, ?string $end = null, string $dateColumn = 'accrual_date', ?int $billId = null) : array
    {
        $startDate = Carbon::parse($start)->startOfMonth();
        $stopDate = Carbon::parse($end)->endOfMonth();
        $effectiveBillId = $billId ?? $this->billId;

        $initQ = FinanceTransaction::whereIn('user_id', $this->getUserIds())
            ->where('is_balance', 1)
            ->where($dateColumn, '<', $startDate);

        $monthlyQ = FinanceTransaction::whereIn('user_id', $this->getUserIds())
            ->where('is_balance', 1)
            ->whereBetween($dateColumn, [$startDate, $stopDate]);

        if ($effectiveBillId) {
            $initQ->where('finance_bill_id', $effectiveBillId);
            $monthlyQ->where('finance_bill_id', $effectiveBillId);
        }

        $initialBalance = $initQ->sum('currency_amount');

        $monthlySums = $monthlyQ
            ->select(DB::raw("DATE_FORMAT($dateColumn, '%Y-%m') as label"), DB::raw('SUM(currency_amount) as value'))
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->pluck('value', 'label')
            ->toArray();

        $months = $startDate->diffInMonths($stopDate) + 1;
        $labels = [];
        $values = [];
        $runningBalance = (float)$initialBalance;

        for ($i = 0; $i < $months; $i++) {
            $monthKey = $startDate->format('Y-m');
            $monthSum = $monthlySums[$monthKey] ?? 0;
            $runningBalance += $monthSum;

            $labels[] = Carbon::createFromFormat('Y-m-d', $monthKey . '-01')->translatedFormat('M Y');
            $values[] = round($runningBalance, 2);

            $startDate->addMonth();
        }

        return [[
            'name' => $name ?? __('Capital'),
            'labels' => $labels,
            'values' => $values
        ]];
    }

    /**
     * Get monthly expenses breakdown for a specific expense source (Monobank style).
     */
    public function getMonthlyExpensesBySource(int $sourceId, ?string $start = null, ?string $end = null, ?int $billId = null, int $monthsCount = 6): array
    {
        $userIds = $this->getUserIds();
        $source = DB::table('finance_transaction_sources')->where('id', $sourceId)->first();
        $sourceName = $source ? $source->name : ('Джерело #' . $sourceId);

        // Determine date range: if start/end provided, use them; otherwise last $monthsCount months up to now
        if ($start && $end) {
            $startDate = Carbon::parse($start)->startOfMonth();
            $endDate = Carbon::parse($end)->endOfMonth();
        } else {
            $endDate = Carbon::now()->endOfMonth();
            $startDate = Carbon::now()->subMonths($monthsCount - 1)->startOfMonth();
        }

        $effectiveBillId = $billId ?? $this->billId;

        $q = DB::table('finance_transactions')
            ->whereIn('user_id', $userIds)
            ->where('is_balance', 1)
            ->whereNull('deleted_at')
            ->where('type', 'expenses')
            ->where('transaction_source_id', $sourceId)
            ->whereBetween('created_at', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')]);

        if ($effectiveBillId) {
            $q->where('finance_bill_id', $effectiveBillId);
        }

        $records = $q->select(
                DB::raw("SUBSTRING(created_at, 1, 7) as month_key"),
                DB::raw("SUM(ABS(currency_amount)) as total_amount"),
                DB::raw("COUNT(*) as tx_count")
            )
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $ukrShortMonths = [
            1 => 'січ', 2 => 'лют', 3 => 'бер', 4 => 'квіт',
            5 => 'трав', 6 => 'черв', 7 => 'лип', 8 => 'серп',
            9 => 'вер', 10 => 'жовт', 11 => 'лист', 12 => 'груд'
        ];

        $ukrFullMonths = [
            1 => 'Січень', 2 => 'Лютий', 3 => 'Березень', 4 => 'Квітень',
            5 => 'Травень', 6 => 'Червень', 7 => 'Липень', 8 => 'Серпень',
            9 => 'Вересень', 10 => 'Жовтень', 11 => 'Листопад', 12 => 'Грудень'
        ];

        $months = [];
        $totalSpent = 0.0;
        $maxMonthAmount = 0.0;
        $totalTxCount = 0;

        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $mKey = $cursor->format('Y-m');
            $mNum = (int)$cursor->format('n');
            $rec = $records->get($mKey);

            $amount = $rec ? (float)$rec->total_amount : 0.0;
            $count = $rec ? (int)$rec->tx_count : 0;

            $totalSpent += $amount;
            $totalTxCount += $count;
            if ($amount > $maxMonthAmount) {
                $maxMonthAmount = $amount;
            }

            $months[] = [
                'key' => $mKey,
                'short_label' => $ukrShortMonths[$mNum] ?? $cursor->format('M'),
                'full_label' => ($ukrFullMonths[$mNum] ?? $cursor->format('F')) . ' ' . $cursor->format('Y'),
                'amount' => $amount,
                'formatted_amount' => number_format($amount, 2, '.', ' ') . ' ₴',
                'count' => $count,
            ];

            $cursor->addMonth();
        }

        // Calculate Y-axis ticks (Monobank style: e.g. 4000 ₴, 2000 ₴, 0 ₴)
        $tickMax = $maxMonthAmount > 0 ? (ceil($maxMonthAmount / 500) * 500) : 1000;
        if ($tickMax < 1000) $tickMax = 1000;
        $tickMid = round($tickMax / 2);

        // Calculate relative heights in percent
        foreach ($months as &$m) {
            $m['height_percent'] = $tickMax > 0 ? round(($m['amount'] / $tickMax) * 100, 1) : 0;
            if ($m['amount'] > 0 && $m['height_percent'] < 5) {
                $m['height_percent'] = 5;
            }
        }
        unset($m);

        $diffMonths = $startDate->diffInMonths($endDate) + 1;
        $periodLabel = ($diffMonths == 6) ? 'за півроку' : ($diffMonths == 12 ? 'за рік' : "за {$diffMonths} міс.");

        return [
            'source' => [
                'id' => $sourceId,
                'name' => $sourceName,
            ],
            'period' => [
                'start' => $startDate->format('d.m.Y'),
                'end' => $endDate->format('d.m.Y'),
                'label' => $periodLabel,
                'months_count' => count($months),
            ],
            'total_spent' => $totalSpent,
            'total_formatted' => number_format($totalSpent, 2, '.', ' ') . ' ₴',
            'total_count' => $totalTxCount,
            'max_month_amount' => $maxMonthAmount,
            'ticks' => [
                'max' => $tickMax,
                'formatted_max' => number_format($tickMax, 0, '.', ' ') . ' ₴',
                'mid' => $tickMid,
                'formatted_mid' => number_format($tickMid, 0, '.', ' ') . ' ₴',
                'zero' => '0 ₴',
            ],
            'months' => $months,
        ];
    }

    /**
     * Get monthly expenses breakdown for top sources.
     */
    public function getTopSourcesMonthlyExpenses(?string $start = null, ?string $end = null, ?int $billId = null, int $topLimit = 5, int $monthsCount = 6): array
    {
        $userIds = $this->getUserIds();
        $effectiveBillId = $billId ?? $this->billId;

        if ($start && $end) {
            $startDate = Carbon::parse($start)->startOfMonth();
            $endDate = Carbon::parse($end)->endOfMonth();
        } else {
            $endDate = Carbon::now()->endOfMonth();
            $startDate = Carbon::now()->subMonths($monthsCount - 1)->startOfMonth();
        }

        $topSourcesQ = DB::table('finance_transactions')
            ->whereIn('finance_transactions.user_id', $userIds)
            ->where('finance_transactions.is_balance', 1)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', 'expenses')
            ->whereNotNull('finance_transactions.transaction_source_id')
            ->whereBetween('finance_transactions.created_at', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')]);

        if ($effectiveBillId) {
            $topSourcesQ->where('finance_transactions.finance_bill_id', $effectiveBillId);
        }

        $topSources = $topSourcesQ
            ->join('finance_transaction_sources', 'finance_transactions.transaction_source_id', '=', 'finance_transaction_sources.id')
            ->select('finance_transaction_sources.id', 'finance_transaction_sources.name', DB::raw('SUM(ABS(finance_transactions.currency_amount)) as total'))
            ->groupBy('finance_transaction_sources.id', 'finance_transaction_sources.name')
            ->orderBy('total', 'desc')
            ->limit($topLimit)
            ->get();

        $result = [];
        foreach ($topSources as $src) {
            $result[] = $this->getMonthlyExpensesBySource((int)$src->id, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $effectiveBillId, $monthsCount);
        }

        return $result;
    }

    public function query(): object
    {
        $query = FinanceTransaction::where('type',$this->getType())->whereIn('user_id', $this->getUserIds())->where('is_balance' ,1);
        if ($this->billId) {
            $query->where('finance_bill_id', $this->billId);
        }
        return $query;
    }

    public function getCurrency(int $bill_id, float $amount): array{
        $bill = FinanceBill::find($bill_id);
        $currency = FinanceCurrency::find($bill->finance_currency_id);
        $transaction['finance_currency_id'] = $bill->finance_currency_id;
        $transaction['currency_code'] =  $bill->currency->code;
        $transaction['currency_value'] = $currency->value;
        $transaction['currency_amount'] = $amount * $currency->value  ;
        $transaction['absolute_currency_amount'] = abs($amount * $currency->value);
        return $transaction;
    }

    public function updateStatusInvoice(int|null $invoice_id): void
    {
        if ($invoice_id) {
            $invoice = FinanceInvoice::find($invoice_id);
            if (!$invoice) {
                return;
            }
            $amount_paid = (float) FinanceTransaction::where('finance_invoice_id', $invoice_id)->sum('amount');
            $data = [
                'amount_paid' => $amount_paid,
            ];

            if ($amount_paid >= (float)$invoice->total && (float)$invoice->total > 0) {
                $data['status'] = 'paid';
            } elseif ($amount_paid > 0) {
                $data['status'] = 'part paid';
            } else {
                $data['status'] = 'not paid';
            }

            $invoice->update($data);
        }
    }
    public function getAmountNegative(float $amount): float{
        return  -abs($amount);
    }
    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserIds(): array
    {
        if (!empty($this->userIds)) {
            return $this->userIds;
        }
        if (Auth::check() && method_exists(Auth::user(), 'getSharedUserIds')) {
            return Auth::user()->getSharedUserIds();
        }
        return [$this->userId];
    }

    public function setTargetUserId(?int $userId = null): self
    {
        if ($userId && $userId > 0) {
            $this->userId = $userId;
            $this->userIds = [$userId];
        } else {
            $this->userId = Auth::id() ?? 0;
            $this->userIds = (Auth::check() && method_exists(Auth::user(), 'getSharedUserIds'))
                ? Auth::user()->getSharedUserIds()
                : [$this->userId];
        }
        return $this;
    }

    public function setUserIds(array $userIds): self
    {
        $this->userIds = $userIds;
        return $this;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
