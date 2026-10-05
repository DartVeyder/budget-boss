<?php

namespace App\Services\Finance\Analytic;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Advanced finance insights for the analytics screen:
 *  - month-end forecast (spending pace projection);
 *  - category changes vs previous period;
 *  - emergency fund ("financial cushion") coverage in months.
 */
class FinanceInsightsService
{
    public const UKR_MONTHS = [
        1 => 'Січень', 2 => 'Лютий', 3 => 'Березень', 4 => 'Квітень',
        5 => 'Травень', 6 => 'Червень', 7 => 'Липень', 8 => 'Серпень',
        9 => 'Вересень', 10 => 'Жовтень', 11 => 'Листопад', 12 => 'Грудень',
    ];

    /** Transaction types that are not real income/expenses: 3 = transfer, 4 = revision. */
    public const NON_FLOW_TYPE_IDS = [3, 4];

    public function __construct(
        protected int $userId,
        protected ?int $billId = null
    ) {
    }

    /* ------------------------------------------------------------------
     |  Queries
     | ------------------------------------------------------------------ */

    protected function baseQuery()
    {
        $q = DB::table('finance_transactions')
            ->where('finance_transactions.user_id', $this->userId)
            ->where('finance_transactions.is_balance', 1)
            ->whereNull('finance_transactions.deleted_at');

        if ($this->billId) {
            $q->where('finance_transactions.finance_bill_id', $this->billId);
        }

        return $q;
    }

    /**
     * Query for real income/expense flows only.
     * Excludes internal transfers (type 3) and balance revisions (type 4):
     * they move/adjust money but are not earning or spending.
     * (Capital still uses baseQuery(), where revisions legitimately correct the balance.)
     */
    protected function flowQuery()
    {
        return $this->baseQuery()->where(function ($q) {
            $q->whereNull('finance_transactions.transaction_type_id')
              ->orWhereNotIn('finance_transactions.transaction_type_id', self::NON_FLOW_TYPE_IDS);
        });
    }

    protected function sumByType(string $type, Carbon $start, Carbon $end): float
    {
        return abs((float)$this->flowQuery()
            ->where('finance_transactions.type', $type)
            ->whereBetween('finance_transactions.created_at', [
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
            ])
            ->sum('finance_transactions.currency_amount'));
    }

    /**
     * Number of distinct months that contain at least one transaction of the type.
     */
    protected function activeMonths(string $type, Carbon $start, Carbon $end): int
    {
        return (int)$this->flowQuery()
            ->where('finance_transactions.type', $type)
            ->whereBetween('finance_transactions.created_at', [
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
            ])
            ->selectRaw("COUNT(DISTINCT DATE_FORMAT(finance_transactions.created_at, '%Y-%m')) as c")
            ->value('c');
    }

    /* ------------------------------------------------------------------
     |  1. Month-end forecast
     | ------------------------------------------------------------------ */

    public function monthForecast(?Carbon $now = null, int $historyMonths = 3): array
    {
        $now        = $now ? $now->copy() : Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $todayEnd   = $now->copy()->endOfDay();

        $spent  = $this->sumByType('expenses', $monthStart, $todayEnd);
        $earned = $this->sumByType('income', $monthStart, $todayEnd);

        $histStart = $monthStart->copy()->subMonths($historyMonths);
        $histEnd   = $monthStart->copy()->subSecond();
        $histDays  = (int)abs(round($histStart->diffInDays($monthStart)));

        $histExpenses = $this->sumByType('expenses', $histStart, $histEnd);
        $histIncome   = $this->sumByType('income', $histStart, $histEnd);
        $expMonths    = max(1, $this->activeMonths('expenses', $histStart, $histEnd));
        $incMonths    = max(1, $this->activeMonths('income', $histStart, $histEnd));

        $result = self::calculateForecast(
            spent: $spent,
            earned: $earned,
            daysElapsed: (int)$now->day,
            daysTotal: (int)$now->daysInMonth,
            histDailyExpenses: $histDays > 0 ? $histExpenses / $histDays : 0.0,
            avgMonthlyExpenses: $histExpenses / $expMonths,
            avgMonthlyIncome: $histIncome / $incMonths,
        );

        $result['month_label']     = (self::UKR_MONTHS[(int)$now->month] ?? $now->format('F')) . ' ' . $now->year;
        $result['history_months']  = $historyMonths;

        return $result;
    }

    /**
     * Pure forecast math.
     *
     * Daily pace is a blend of the current month pace and the historical
     * daily average: at the start of the month the history dominates
     * (current pace is noisy), by the end the actual pace dominates.
     */
    public static function calculateForecast(
        float $spent,
        float $earned,
        int $daysElapsed,
        int $daysTotal,
        float $histDailyExpenses,
        float $avgMonthlyExpenses,
        float $avgMonthlyIncome
    ): array {
        $daysTotal   = max(1, $daysTotal);
        $daysElapsed = max(1, min($daysElapsed, $daysTotal));
        $daysLeft    = $daysTotal - $daysElapsed;

        $currentDaily = $spent / $daysElapsed;
        $weight       = $daysElapsed / $daysTotal;

        $dailyPace = $histDailyExpenses > 0
            ? ($currentDaily * $weight) + ($histDailyExpenses * (1 - $weight))
            : $currentDaily;

        $projectedExpenses = $spent + ($dailyPace * $daysLeft);
        // Income is lumpy (salary, invoices) — don't extrapolate it linearly.
        $projectedIncome   = max($earned, $avgMonthlyIncome);
        $projectedNet      = $projectedIncome - $projectedExpenses;

        $vsAvgPercent = $avgMonthlyExpenses > 0
            ? round((($projectedExpenses - $avgMonthlyExpenses) / $avgMonthlyExpenses) * 100, 1)
            : null;

        // How much can be spent per day for the rest of the month to end at zero.
        $safeDaily = $daysLeft > 0
            ? max(0.0, ($projectedIncome - $spent) / $daysLeft)
            : 0.0;

        return [
            'spent'               => round($spent, 2),
            'earned'              => round($earned, 2),
            'days_elapsed'        => $daysElapsed,
            'days_total'          => $daysTotal,
            'days_left'           => $daysLeft,
            'month_progress'      => round($weight * 100, 1),
            'current_daily'       => round($currentDaily, 2),
            'daily_pace'          => round($dailyPace, 2),
            'safe_daily'          => round($safeDaily, 2),
            'projected_expenses'  => round($projectedExpenses, 2),
            'projected_income'    => round($projectedIncome, 2),
            'projected_net'       => round($projectedNet, 2),
            'avg_monthly_expenses'=> round($avgMonthlyExpenses, 2),
            'avg_monthly_income'  => round($avgMonthlyIncome, 2),
            'vs_avg_percent'      => $vsAvgPercent,
            'spent_share'         => $projectedExpenses > 0 ? round(min(100, $spent / $projectedExpenses * 100), 1) : 0,
        ];
    }

    /* ------------------------------------------------------------------
     |  2. Category changes vs previous period
     | ------------------------------------------------------------------ */

    public function categoryChanges(string $type, string $start, string $end, string $prevStart, string $prevEnd, int $limit = 10): array
    {
        $current  = $this->categoryTotals($type, $start, $end);
        $previous = $this->categoryTotals($type, $prevStart, $prevEnd);

        return self::buildCategoryChanges($current, $previous, $type, $limit);
    }

    /**
     * @return array<string, array{id: int|null, name: string, total: float}>
     */
    protected function categoryTotals(string $type, string $start, string $end): array
    {
        return $this->flowQuery()
            ->where('finance_transactions.type', $type)
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->leftJoin('finance_transaction_categories as c', 'finance_transactions.transaction_category_id', '=', 'c.id')
            ->select(
                'finance_transactions.transaction_category_id as id',
                DB::raw('MAX(c.name) as name'),
                DB::raw('ABS(SUM(finance_transactions.currency_amount)) as total')
            )
            ->groupBy('finance_transactions.transaction_category_id')
            ->get()
            ->mapWithKeys(fn ($r) => [
                (string)($r->id ?? 'none') => [
                    'id'    => $r->id !== null ? (int)$r->id : null,
                    'name'  => $r->name ?: 'Без категорії',
                    'total' => (float)$r->total,
                ],
            ])
            ->all();
    }

    /**
     * Pure merge/diff of two category => total maps.
     */
    public static function buildCategoryChanges(array $current, array $previous, string $type, int $limit = 10): array
    {
        $rows = [];
        foreach (array_unique(array_merge(array_keys($current), array_keys($previous))) as $key) {
            $cur  = (float)($current[$key]['total'] ?? 0);
            $prev = (float)($previous[$key]['total'] ?? 0);
            if (abs($cur) < 0.01 && abs($prev) < 0.01) {
                continue;
            }

            $diff = $cur - $prev;
            $rows[] = [
                'id'           => $current[$key]['id'] ?? $previous[$key]['id'] ?? null,
                'name'         => $current[$key]['name'] ?? $previous[$key]['name'] ?? 'Без категорії',
                'current'      => round($cur, 2),
                'prev'         => round($prev, 2),
                'diff'         => round($diff, 2),
                'percent'      => $prev > 0.01 ? round($diff / $prev * 100, 1) : null,
                'is_new'       => $prev <= 0.01 && $cur > 0.01,
                'is_gone'      => $cur <= 0.01 && $prev > 0.01,
                'is_favorable' => $type === 'expenses' ? $diff <= 0 : $diff >= 0,
            ];
        }

        usort($rows, fn ($a, $b) => abs($b['diff']) <=> abs($a['diff']));
        $rows = array_slice($rows, 0, $limit);

        $maxAbs = max(array_map(fn ($r) => abs($r['diff']), $rows) ?: [0]);
        foreach ($rows as &$r) {
            $r['bar'] = $maxAbs > 0 ? round(abs($r['diff']) / $maxAbs * 100, 1) : 0;
        }
        unset($r);

        $totalCur  = array_sum(array_column($current, 'total'));
        $totalPrev = array_sum(array_column($previous, 'total'));

        return [
            'type'       => $type,
            'rows'       => $rows,
            'total_cur'  => round($totalCur, 2),
            'total_prev' => round($totalPrev, 2),
            'total_diff' => round($totalCur - $totalPrev, 2),
        ];
    }

    /* ------------------------------------------------------------------
     |  3. Emergency fund (financial cushion)
     | ------------------------------------------------------------------ */

    public function emergencyFund(?Carbon $now = null, int $historyMonths = 6, int $targetMonths = 6): array
    {
        $now        = $now ? $now->copy() : Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $histStart  = $monthStart->copy()->subMonths($historyMonths);
        $histEnd    = $monthStart->copy()->subSecond();

        $capital      = (float)$this->baseQuery()->sum('finance_transactions.currency_amount');
        $histExpenses = $this->sumByType('expenses', $histStart, $histEnd);
        $activeMonths = max(1, min($historyMonths, $this->activeMonths('expenses', $histStart, $histEnd)));

        $result = self::calculateEmergencyFund($capital, $histExpenses / $activeMonths, $targetMonths);
        $result['history_months'] = $activeMonths;

        return $result;
    }

    public static function calculateEmergencyFund(float $capital, float $avgMonthlyExpenses, int $targetMonths = 6): array
    {
        $months = $avgMonthlyExpenses > 0 ? max(0.0, $capital) / $avgMonthlyExpenses : null;
        $target = $avgMonthlyExpenses * $targetMonths;

        if ($months === null) {
            $status = 'unknown';
        } elseif ($months < 1) {
            $status = 'critical';
        } elseif ($months < 3) {
            $status = 'low';
        } elseif ($months < $targetMonths) {
            $status = 'ok';
        } else {
            $status = 'great';
        }

        return [
            'capital'              => round($capital, 2),
            'avg_monthly_expenses' => round($avgMonthlyExpenses, 2),
            'months'               => $months !== null ? round($months, 1) : null,
            'target_months'        => $targetMonths,
            'target_amount'        => round($target, 2),
            'shortfall'            => round(max(0, $target - max(0, $capital)), 2),
            'progress'             => $target > 0 ? round(min(100, max(0, $capital) / $target * 100), 1) : 0,
            'status'               => $status,
        ];
    }
}
