<?php

namespace App\Services\Finance\Fop;

use App\Models\Act;
use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\Fop;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FopAnalyticService
{
    protected FopTaxService $taxService;

    public function __construct(?FopTaxService $taxService = null)
    {
        $this->taxService = $taxService ?? new FopTaxService();
    }

    /**
     * Get active FOP for the user
     */
    public function getFop(?int $userId = null): ?Fop
    {
        return $this->taxService->getFop($userId);
    }

    /**
     * Base query for FOP income transactions
     */
    public function getFopIncomeQuery(Fop $fop, ?string $startDate = null, ?string $endDate = null)
    {
        $query = FinanceTransaction::query()
            ->where('finance_transactions.user_id', $fop->user_id)
            ->where('finance_transactions.type', 'income')
            ->where('finance_transactions.is_balance', 1)
            ->where(function ($q) {
                $q->whereNull('finance_transactions.transaction_type_id')
                  ->orWhere('finance_transactions.transaction_type_id', '!=', 3);
            })
            ->where(function ($q) use ($fop) {
                $q->where('finance_transactions.fop_id', $fop->id);
                if ($fop->finance_bill_id) {
                    $q->orWhere('finance_transactions.finance_bill_id', $fop->finance_bill_id);
                }
            });

        if ($startDate && $endDate) {
            $query->whereBetween('finance_transactions.created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('finance_transactions.created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('finance_transactions.created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Get KPI summary metrics for the given period
     */
    public function getKpiSummary(Fop $fop, int $year, ?int $quarter = null): array
    {
        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfDay()->toDateTimeString();
            $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth()->endOfDay()->toDateTimeString();

            // Previous quarter for comparison
            $prevQuarter = $quarter === 1 ? 4 : $quarter - 1;
            $prevYear = $quarter === 1 ? $year - 1 : $year;
            $prevStartMonth = ($prevQuarter - 1) * 3 + 1;
            $prevEndMonth = $prevQuarter * 3;
            $prevStartDate = Carbon::createFromDate($prevYear, $prevStartMonth, 1)->startOfDay()->toDateTimeString();
            $prevEndDate = Carbon::createFromDate($prevYear, $prevEndMonth, 1)->endOfMonth()->endOfDay()->toDateTimeString();
        } else {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay()->toDateTimeString();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay()->toDateTimeString();

            // Previous year for comparison
            $prevStartDate = Carbon::createFromDate($year - 1, 1, 1)->startOfDay()->toDateTimeString();
            $prevEndDate = Carbon::createFromDate($year - 1, 12, 31)->endOfDay()->toDateTimeString();
        }

        // Current period income
        $currentQuery = $this->getFopIncomeQuery($fop, $startDate, $endDate);
        $totalIncome = (float)$currentQuery->sum('currency_amount');
        $txCount = (int)$currentQuery->count();
        $avgIncome = $txCount > 0 ? round($totalIncome / $txCount, 2) : 0.0;

        // Previous period income
        $prevQuery = $this->getFopIncomeQuery($fop, $prevStartDate, $prevEndDate);
        $prevIncome = (float)$prevQuery->sum('currency_amount');

        // Growth percentage
        $growthPercent = 0.0;
        if ($prevIncome > 0) {
            $growthPercent = round((($totalIncome - $prevIncome) / $prevIncome) * 100, 1);
        } elseif ($totalIncome > 0) {
            $growthPercent = 100.0;
        }

        // Taxes calculation (Single tax 5%, Military tax 1%, ESV)
        $fop->loadMissing('fopGroup.taxRates');
        $rates = $fop->fopGroup?->taxRates ?? collect();

        $singleRate = (float)($rates->first(function ($r) {
            $n = mb_strtolower($r->name);
            return str_contains($n, 'єдин') || (float)$r->value === 5.0;
        })?->value ?? 5.0);

        $militaryRate = (float)($rates->first(function ($r) {
            $n = mb_strtolower($r->name);
            return str_contains($n, 'військов') || (float)$r->value === 1.0;
        })?->value ?? 1.0);

        $monthlyEsv = (float)$fop->effective_monthly_esv;
        $esvMonths = $quarter ? 3 : 12;
        $esvTotal = $fop->is_esv_exempt ? 0.0 : ($monthlyEsv * $esvMonths);

        $singleTax = round($totalIncome * ($singleRate / 100), 2);
        $militaryTax = round($totalIncome * ($militaryRate / 100), 2);
        $totalTaxesAccrued = $singleTax + $militaryTax + $esvTotal;
        $netIncome = max(0.0, $totalIncome - $totalTaxesAccrued);
        $effectiveTaxRate = $totalIncome > 0 ? round(($totalTaxesAccrued / $totalIncome) * 100, 2) : 0.0;

        // Taxes paid
        $taxPaidQuery = FinanceTransaction::where('type', 'expenses')
            ->where('tax_year', $year)
            ->where(function ($q) use ($fop) {
                $q->where('fop_id', $fop->id);
                if ($fop->finance_bill_id) {
                    $q->orWhere('finance_bill_id', $fop->finance_bill_id);
                }
            })
            ->whereIn('tax_type', ['single_tax', 'military_tax', 'esv']);

        if ($quarter) {
            $taxPaidQuery->where('tax_quarter', $quarter);
        }
        $taxesPaid = (float)$taxPaidQuery->sum(DB::raw('ABS(amount)'));

        // FOP Bill balance
        $billBalance = 0.0;
        if ($fop->finance_bill_id) {
            $billBalance = (float)FinanceTransaction::where('user_id', $fop->user_id)
                ->where('finance_bill_id', $fop->finance_bill_id)
                ->sum('amount');
        }

        // Limit progress for current year
        $limitProgress = $this->taxService->getLimitProgress($fop, $year);

        return [
            'year' => $year,
            'quarter' => $quarter,
            'total_income' => $totalIncome,
            'prev_income' => $prevIncome,
            'growth_percent' => $growthPercent,
            'tx_count' => $txCount,
            'avg_income' => $avgIncome,
            'single_tax' => $singleTax,
            'military_tax' => $militaryTax,
            'esv_total' => $esvTotal,
            'total_taxes_accrued' => $totalTaxesAccrued,
            'taxes_paid' => $taxesPaid,
            'net_income' => $netIncome,
            'effective_tax_rate' => $effectiveTaxRate,
            'bill_balance' => $billBalance,
            'limit_progress' => $limitProgress,
        ];
    }

    /**
     * Monthly revenue chart data comparing current year vs previous year
     */
    public function getMonthlyRevenueChart(Fop $fop, int $year): array
    {
        $months = [
            1 => 'Січ', 2 => 'Лют', 3 => 'Бер', 4 => 'Кві',
            5 => 'Тра', 6 => 'Чер', 7 => 'Лип', 8 => 'Сер',
            9 => 'Вер', 10 => 'Жов', 11 => 'Лис', 12 => 'Гру'
        ];

        $currentYearSums = $this->getMonthlySumsForYear($fop, $year);
        $prevYearSums = $this->getMonthlySumsForYear($fop, $year - 1);

        $labels = array_values($months);
        $currentValues = [];
        $prevValues = [];

        for ($m = 1; $m <= 12; $m++) {
            $currentValues[] = $currentYearSums[$m] ?? 0.0;
            $prevValues[] = $prevYearSums[$m] ?? 0.0;
        }

        return [
            [
                'name' => "Дохід {$year} (грн)",
                'type' => 'bar',
                'values' => $currentValues,
                'labels' => $labels,
            ],
            [
                'name' => "Дохід " . ($year - 1) . " (грн)",
                'type' => 'line',
                'values' => $prevValues,
                'labels' => $labels,
            ]
        ];
    }

    private function getMonthlySumsForYear(Fop $fop, int $year): array
    {
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay()->toDateTimeString();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay()->toDateTimeString();

        $rows = $this->getFopIncomeQuery($fop, $startDate, $endDate)
            ->selectRaw('MONTH(created_at) as month, SUM(currency_amount) as total')
            ->groupBy('month')
            ->get();

        $sums = [];
        foreach ($rows as $row) {
            $sums[(int)$row->month] = round((float)$row->total, 2);
        }

        return $sums;
    }

    /**
     * Detailed quarterly breakdown table
     */
    public function getQuarterlyBreakdown(Fop $fop, int $year): array
    {
        $fop->loadMissing('fopGroup.taxRates');
        $rates = $fop->fopGroup?->taxRates ?? collect();

        $singleRate = (float)($rates->first(function ($r) {
            $n = mb_strtolower($r->name);
            return str_contains($n, 'єдин') || (float)$r->value === 5.0;
        })?->value ?? 5.0);

        $militaryRate = (float)($rates->first(function ($r) {
            $n = mb_strtolower($r->name);
            return str_contains($n, 'військов') || (float)$r->value === 1.0;
        })?->value ?? 1.0);

        $monthlyEsv = (float)$fop->effective_monthly_esv;
        $quarterlyEsv = $fop->is_esv_exempt ? 0.0 : ($monthlyEsv * 3);

        $quarters = [];
        $runningIncome = 0.0;

        for ($q = 1; $q <= 4; $q++) {
            $startMonth = ($q - 1) * 3 + 1;
            $endMonth = $q * 3;

            $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfDay()->toDateTimeString();
            $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth()->endOfDay()->toDateTimeString();

            $query = $this->getFopIncomeQuery($fop, $startDate, $endDate);
            $income = (float)$query->sum('currency_amount');
            $txCount = (int)$query->count();
            $runningIncome += $income;

            $singleTax = round($income * ($singleRate / 100), 2);
            $militaryTax = round($income * ($militaryRate / 100), 2);
            $totalTax = $singleTax + $militaryTax + $quarterlyEsv;
            $net = max(0.0, $income - $totalTax);

            // Taxes paid
            $paid = (float)FinanceTransaction::where('type', 'expenses')
                ->where('tax_year', $year)
                ->where('tax_quarter', $q)
                ->where(function ($sq) use ($fop) {
                    $sq->where('fop_id', $fop->id);
                    if ($fop->finance_bill_id) {
                        $sq->orWhere('finance_bill_id', $fop->finance_bill_id);
                    }
                })
                ->whereIn('tax_type', ['single_tax', 'military_tax', 'esv'])
                ->sum(DB::raw('ABS(amount)'));

            $quarters[$q] = [
                'quarter' => $q,
                'label' => "{$q} квартал {$year}",
                'income' => $income,
                'cumulative_income' => $runningIncome,
                'tx_count' => $txCount,
                'single_tax' => $singleTax,
                'military_tax' => $militaryTax,
                'esv' => $quarterlyEsv,
                'total_tax' => $totalTax,
                'tax_paid' => $paid,
                'tax_balance' => max(0.0, $totalTax - $paid),
                'is_paid' => $paid >= $totalTax && $totalTax > 0,
                'net_income' => $net,
                'effective_rate' => $income > 0 ? round(($totalTax / $income) * 100, 2) : 0.0,
            ];
        }

        return $quarters;
    }

    /**
     * Customer analytics breakdown (portfolio distribution)
     */
    public function getCustomerAnalytics(Fop $fop, int $year, ?int $quarter = null): array
    {
        $dates = $this->getPeriodDates($year, $quarter);
        $totalIncome = (float)$this->getFopIncomeQuery($fop, $dates['start'], $dates['end'])->sum('currency_amount');
        $totalIncome = $totalIncome > 0 ? $totalIncome : 1.0;

        $rows = $this->getFopIncomeQuery($fop, $dates['start'], $dates['end'])
            ->leftJoin('customers', 'finance_transactions.customer_id', '=', 'customers.id')
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('COUNT(finance_transactions.id) as tx_count'),
                DB::raw('SUM(finance_transactions.currency_amount) as total_amount'),
                DB::raw('MAX(finance_transactions.created_at) as last_payment_date')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4', '#f97316', '#14b8a6', '#64748b'];
        $items = [];
        $colorIdx = 0;

        foreach ($rows as $row) {
            $amount = (float)$row->total_amount;
            $percent = round(($amount / $totalIncome) * 100, 2);
            $name = $row->name ?: 'Інші замовники (без прив\'язки)';

            // Count signed acts
            $actsCount = 0;
            if ($row->id) {
                $actsCount = Act::where('fop_id', $fop->id)
                    ->where('customer_id', $row->id)
                    ->whereYear('act_date', $year)
                    ->count();
            }

            $items[] = [
                'customer_id' => $row->id,
                'name' => $name,
                'amount' => $amount,
                'formatted_amount' => number_format($amount, 2, '.', ' ') . ' ₴',
                'percent' => $percent,
                'tx_count' => (int)$row->tx_count,
                'avg_amount' => (int)$row->tx_count > 0 ? round($amount / (int)$row->tx_count, 2) : 0.0,
                'acts_count' => $actsCount,
                'last_payment_date' => $row->last_payment_date ? Carbon::parse($row->last_payment_date)->format('d.m.Y') : '—',
                'color' => $colors[$colorIdx % count($colors)],
            ];
            $colorIdx++;
        }

        return $items;
    }

    /**
     * Counterparty analytics breakdown (specific legal entities/FOP counterparties)
     */
    public function getCounterpartyAnalytics(Fop $fop, int $year, ?int $quarter = null): array
    {
        $dates = $this->getPeriodDates($year, $quarter);
        $totalIncome = (float)$this->getFopIncomeQuery($fop, $dates['start'], $dates['end'])->sum('currency_amount');
        $totalIncome = $totalIncome > 0 ? $totalIncome : 1.0;

        $rows = $this->getFopIncomeQuery($fop, $dates['start'], $dates['end'])
            ->leftJoin('customer_counterparties', 'finance_transactions.counterparty_id', '=', 'customer_counterparties.id')
            ->select(
                'customer_counterparties.id',
                'customer_counterparties.name',
                'customer_counterparties.ipn',
                DB::raw('COUNT(finance_transactions.id) as tx_count'),
                DB::raw('SUM(finance_transactions.currency_amount) as total_amount'),
                DB::raw('MAX(finance_transactions.created_at) as last_payment_date')
            )
            ->groupBy('customer_counterparties.id', 'customer_counterparties.name', 'customer_counterparties.ipn')
            ->orderBy('total_amount', 'desc')
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $amount = (float)$row->total_amount;
            $percent = round(($amount / $totalIncome) * 100, 2);
            $name = $row->name ?: 'Прямі надходження (без контрагента)';

            $items[] = [
                'counterparty_id' => $row->id,
                'name' => $name,
                'ipn' => $row->ipn ?: '—',
                'amount' => $amount,
                'formatted_amount' => number_format($amount, 2, '.', ' ') . ' ₴',
                'percent' => $percent,
                'tx_count' => (int)$row->tx_count,
                'avg_amount' => (int)$row->tx_count > 0 ? round($amount / (int)$row->tx_count, 2) : 0.0,
                'last_payment_date' => $row->last_payment_date ? Carbon::parse($row->last_payment_date)->format('d.m.Y') : '—',
            ];
        }

        return $items;
    }

    /**
     * Document closure / compliance rate (how many income payments have Acts)
     */
    public function getDocumentCompliance(Fop $fop, int $year): array
    {
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay()->toDateTimeString();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay()->toDateTimeString();

        $totalQuery = $this->getFopIncomeQuery($fop, $startDate, $endDate);
        $totalTx = (int)$totalQuery->count();
        $totalAmount = (float)$totalQuery->sum('currency_amount');

        $closedQuery = $this->getFopIncomeQuery($fop, $startDate, $endDate)->has('act');
        $closedTx = (int)$closedQuery->count();
        $closedAmount = (float)$closedQuery->sum('currency_amount');

        $unclosedTx = max(0, $totalTx - $closedTx);
        $unclosedAmount = max(0.0, $totalAmount - $closedAmount);
        $percent = $totalTx > 0 ? round(($closedTx / $totalTx) * 100, 1) : 0.0;

        return [
            'total_tx' => $totalTx,
            'total_amount' => $totalAmount,
            'closed_tx' => $closedTx,
            'closed_amount' => $closedAmount,
            'unclosed_tx' => $unclosedTx,
            'unclosed_amount' => $unclosedAmount,
            'compliance_percent' => $percent,
        ];
    }

    /**
     * Recent FOP transactions
     */
    public function getRecentFopTransactions(Fop $fop, int $limit = 8): Collection
    {
        return $this->getFopIncomeQuery($fop)
            ->with(['customer', 'counterparty', 'act', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Available years for filtering
     */
    public function getAvailableYears(Fop $fop): array
    {
        $years = $this->getFopIncomeQuery($fop)
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->map(fn($y) => (int)$y)
            ->toArray();

        $currentYear = (int)Carbon::now()->year;
        if (!in_array($currentYear, $years, true)) {
            array_unshift($years, $currentYear);
        }

        return $years;
    }

    private function getPeriodDates(int $year, ?int $quarter = null): array
    {
        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $start = Carbon::createFromDate($year, $startMonth, 1)->startOfDay()->toDateTimeString();
            $end = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth()->endOfDay()->toDateTimeString();
        } else {
            $start = Carbon::createFromDate($year, 1, 1)->startOfDay()->toDateTimeString();
            $end = Carbon::createFromDate($year, 12, 31)->endOfDay()->toDateTimeString();
        }

        return ['start' => $start, 'end' => $end];
    }
}
