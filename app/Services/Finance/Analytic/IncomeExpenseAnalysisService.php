<?php

declare(strict_types=1);

namespace App\Services\Finance\Analytic;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for in-depth income and expense analysis,
 * expense concentration & leak detection,
 * and monthly income allocation recommendations (50/30/20 & 6 Jars).
 */
class IncomeExpenseAnalysisService
{
    public const BUCKET_NEEDS = 'needs';
    public const BUCKET_WANTS = 'wants';
    public const BUCKET_SAVINGS = 'savings';
    public const BUCKET_EDUCATION = 'education';
    public const BUCKET_LONG_TERM = 'long_term';
    public const BUCKET_GIVE = 'give';
    public const BUCKET_OTHER = 'other';

    /** Transaction types that are not real income/expenses: 3 = transfer, 4 = revision. */
    public const NON_FLOW_TYPE_IDS = [3, 4];

    public function __construct(
        protected int|array $userId,
        protected ?int $billId = null
    ) {
    }

    /* ------------------------------------------------------------------
     |  Base queries
     | ------------------------------------------------------------------ */

    protected function baseQuery()
    {
        $q = DB::table('finance_transactions')
            ->where('finance_transactions.is_balance', 1)
            ->whereNull('finance_transactions.deleted_at');

        if (is_array($this->userId)) {
            $q->whereIn('finance_transactions.user_id', $this->userId);
        } else {
            $q->where('finance_transactions.user_id', $this->userId);
        }

        if ($this->billId) {
            $q->where('finance_transactions.finance_bill_id', $this->billId);
        }

        return $q;
    }

    protected function flowQuery()
    {
        return $this->baseQuery()->where(function ($q) {
            $q->whereNull('finance_transactions.transaction_type_id')
              ->orWhereNotIn('finance_transactions.transaction_type_id', self::NON_FLOW_TYPE_IDS);
        });
    }

    /* ------------------------------------------------------------------
     |  Category Classification
     | ------------------------------------------------------------------ */

    /**
     * Categorize a category name into a financial bucket.
     */
    public static function classifyCategory(?string $name): string
    {
        if (!$name) {
            return self::BUCKET_OTHER;
        }

        $lower = mb_strtolower(trim($name), 'UTF-8');

        // 1. Savings & Investments (FFA)
        $savingsKeywords = [
            'депозит', 'заощаджен', 'накопичен', 'інвестиц', 'крипт',
            'binance', 'подушк', 'фонд', 'акці', 'облігаці', 'овдп',
            'резерв', 'скарбничк', 'заощадження', 'капітал',
        ];
        foreach ($savingsKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return self::BUCKET_SAVINGS;
            }
        }

        // 2. Education & Personal Growth (EDU)
        $educationKeywords = [
            'навчан', 'освіт', 'курс', 'книг', 'тренінг', 'семінар',
            'вебінар', 'репетитор', 'школ', 'універ', 'англійськ',
        ];
        foreach ($educationKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return self::BUCKET_EDUCATION;
            }
        }

        // 3. Long Term Savings & Big Assets / Repairs (LTSS)
        $longTermKeywords = [
            'ремонт', 'будівництв', 'мебл', 'автомобіль', 'машин',
            'покупка техніки', 'побутова техніка',
        ];
        foreach ($longTermKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return self::BUCKET_LONG_TERM;
            }
        }

        // 4. Charity & Gifts (GIVE)
        $giveKeywords = [
            'благодійн', 'донат', 'збір', 'зсу', 'армі', 'волонтер',
            'подарунк', 'подарок', 'квіт',
        ];
        foreach ($giveKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return self::BUCKET_GIVE;
            }
        }

        // 5. Basic Necessities (Needs)
        $needsKeywords = [
            'продукт', 'супермаркет', 'їжа', 'харчуван', 'житло',
            'проживан', 'оренд', 'комунал', 'інтернет', 'світл',
            'газ', 'вод', 'транспорт', 'заправк', 'палив', 'бензин',
            'здоров', 'ліки', 'аптек', 'медицин', 'лікар', 'подат',
            'військов', 'дров', 'тепло', 'зоо', 'тварини', 'штраф',
            'канцтовар', 'кур\'єр', 'поповнен', 'мобільн', 'зв\'яз',
            'комунальні', 'дрова', 'податок', 'зоомагазин',
        ];
        foreach ($needsKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return self::BUCKET_NEEDS;
            }
        }

        // 6. Wants & Lifestyle
        $wantsKeywords = [
            'кафе', 'ресторан', 'бар', 'фастфуд', 'кава', 'розваг',
            'дозвілл', 'одяг', 'взуття', 'гардероб', 'кіно', 'театр',
            'концерт', 'підписк', 'netfl', 'spot', 'youtube',
            'таксі', 'uber', 'bolt', 'технік', 'гаджет', 'шопінг',
            'весілл', 'свято', 'крас', 'салон', 'перукар', 'барбер',
            'парфум', 'спортзал', 'фітнес', 'відпочинок', 'подорож',
            'готель', 'хобі', 'ігри', 'магазин',
        ];
        foreach ($wantsKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return self::BUCKET_WANTS;
            }
        }

        return self::BUCKET_OTHER;
    }

    /**
     * Map bucket key to Ukrainian readable label and icon.
     */
    public static function getBucketMetadata(string $bucket): array
    {
        return match ($bucket) {
            self::BUCKET_NEEDS => [
                'name' => 'Базові потреби',
                'short_name' => 'Потреби (Needs)',
                'icon' => '🏠',
                'color' => '#3b82f6', // blue
                'bg' => '#eff6ff',
                'description' => 'Житло, комунальні, продукти, транспорт, здоров\'я, зв\'язок',
            ],
            self::BUCKET_WANTS => [
                'name' => 'Бажання та комфорт',
                'short_name' => 'Бажання (Wants)',
                'icon' => '☕',
                'color' => '#f59e0b', // amber
                'bg' => '#fffbeb',
                'description' => 'Кафе, ресторани, шопінг, розваги, таксі, підписки',
            ],
            self::BUCKET_SAVINGS => [
                'name' => 'Заощадження та інвестиції',
                'short_name' => 'Заощадження (Savings)',
                'icon' => '📈',
                'color' => '#10b981', // green
                'bg' => '#f0fdf4',
                'description' => 'Депозити, подушка безпеки, інвестиції, криптовалюта',
            ],
            self::BUCKET_EDUCATION => [
                'name' => 'Освіта та розвиток',
                'short_name' => 'Освіта (EDU)',
                'icon' => '📚',
                'color' => '#8b5cf6', // purple
                'bg' => '#f5f3ff',
                'description' => 'Книги, курси, тренінги, професійні навички',
            ],
            self::BUCKET_LONG_TERM => [
                'name' => 'Довгострокові витрати',
                'short_name' => 'Резерв (LTSS)',
                'icon' => '🛠️',
                'color' => '#06b6d4', // cyan
                'bg' => '#ecfeff',
                'description' => 'Ремонт, техніка, накопичення на великі покупки',
            ],
            self::BUCKET_GIVE => [
                'name' => 'Благодійність та подарунки',
                'short_name' => 'Благодійність (GIVE)',
                'icon' => '🎁',
                'color' => '#ec4899', // pink
                'bg' => '#fdf2f8',
                'description' => 'Подарунки близьким, донати, допомога іншим',
            ],
            default => [
                'name' => 'Інші витрати',
                'short_name' => 'Інше',
                'icon' => '📦',
                'color' => '#64748b', // slate
                'bg' => '#f8fafc',
                'description' => 'Некласифіковані або дрібні витрати',
            ],
        };
    }

    /* ------------------------------------------------------------------
     |  Comprehensive Analysis Engine
     | ------------------------------------------------------------------ */

    /**
     * Run full analysis and return all data structures for UI.
     */
    public function analyze(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $now = Carbon::now();

        // If not provided, default to current month
        $start = $startDate ? $startDate->copy() : $now->copy()->startOfMonth();
        $end   = $endDate   ? $endDate->copy()   : $now->copy()->endOfMonth();

        $startStr = $start->format('Y-m-d H:i:s');
        $endStr   = $end->format('Y-m-d H:i:s');

        // Current month bounds for monthly context
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $monthEnd   = $now->copy()->endOfMonth()->format('Y-m-d H:i:s');

        // 1. Period totals
        $periodIncome = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'income')
            ->whereBetween('finance_transactions.created_at', [$startStr, $endStr])
            ->sum('finance_transactions.currency_amount'));

        $periodExpenses = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'expenses')
            ->whereBetween('finance_transactions.created_at', [$startStr, $endStr])
            ->sum('finance_transactions.currency_amount'));

        // 2. Current Month totals (always available for accurate monthly budgeting)
        $monthIncome = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'income')
            ->whereBetween('finance_transactions.created_at', [$monthStart, $monthEnd])
            ->sum('finance_transactions.currency_amount'));

        $monthExpenses = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'expenses')
            ->whereBetween('finance_transactions.created_at', [$monthStart, $monthEnd])
            ->sum('finance_transactions.currency_amount'));

        // 3. Historical averages (last 3 full months)
        $histStart = $now->copy()->startOfMonth()->subMonths(3)->format('Y-m-d H:i:s');
        $histEnd   = $now->copy()->startOfMonth()->subSecond()->format('Y-m-d H:i:s');

        $histIncome = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'income')
            ->whereBetween('finance_transactions.created_at', [$histStart, $histEnd])
            ->sum('finance_transactions.currency_amount'));

        $histExpenses = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'expenses')
            ->whereBetween('finance_transactions.created_at', [$histStart, $histEnd])
            ->sum('finance_transactions.currency_amount'));

        $histMonthsCount = max(1, (int)$this->flowQuery()
            ->where('finance_transactions.type', 'expenses')
            ->whereBetween('finance_transactions.created_at', [$histStart, $histEnd])
            ->selectRaw("COUNT(DISTINCT DATE_FORMAT(finance_transactions.created_at, '%Y-%m')) as c")
            ->value('c'));

        $avgMonthlyIncome   = round($histIncome / $histMonthsCount, 2);
        $avgMonthlyExpenses = round($histExpenses / $histMonthsCount, 2);

        // Reference monthly income for allocation recommendations
        // Priority: current month income (if > 0) -> avg monthly income -> period income -> expenses
        $referenceMonthlyIncome = $monthIncome > 0
            ? $monthIncome
            : ($avgMonthlyIncome > 0 ? $avgMonthlyIncome : ($periodIncome > 0 ? $periodIncome : $periodExpenses));

        // 4. Detailed category breakdown for expenses
        $categoryBreakdown = $this->getCategoryBreakdown($startStr, $endStr, $periodExpenses, $periodIncome);

        // 5. Financial buckets calculation (for 50/30/20 & 6 Jars)
        $bucketTotals = $this->calculateBucketTotals($categoryBreakdown, $periodExpenses);

        // 6. 50/30/20 Model Allocation
        $allocation503020 = $this->build503020Allocation($referenceMonthlyIncome, $periodIncome, $periodExpenses, $bucketTotals);

        // 7. 6 Jars Model Allocation
        $allocation6Jars = $this->build6JarsAllocation($referenceMonthlyIncome, $bucketTotals);

        // 8. Expense concentration & leak detectors ("На що більше йдуть витрати")
        $concentration = $this->buildConcentrationAnalysis($categoryBreakdown, $periodExpenses, $periodIncome);

        // 9. Actionable Smart Recommendations
        $recommendations = $this->generateRecommendations(
            periodIncome: $periodIncome,
            periodExpenses: $periodExpenses,
            referenceIncome: $referenceMonthlyIncome,
            allocation: $allocation503020,
            concentration: $concentration,
            avgExpenses: $avgMonthlyExpenses,
            now: $now
        );

        $daysInPeriod = max(1, $start->diffInDays($end) + 1);

        return [
            'period' => [
                'start' => $startStr,
                'end' => $endStr,
                'start_formatted' => $start->format('d.m.Y'),
                'end_formatted' => $end->format('d.m.Y'),
                'days' => $daysInPeriod,
                'is_current_month' => $start->isSameMonth($now) && $end->isSameMonth($now),
                'month_name' => FinanceInsightsService::UKR_MONTHS[(int)$now->month] . ' ' . $now->year,
            ],
            'summary' => [
                'period_income' => $periodIncome,
                'period_expenses' => $periodExpenses,
                'net_flow' => $periodIncome - $periodExpenses,
                'savings_rate' => $periodIncome > 0 ? round((($periodIncome - $periodExpenses) / $periodIncome) * 100, 1) : 0.0,
                'month_income' => $monthIncome,
                'month_expenses' => $monthExpenses,
                'month_net' => $monthIncome - $monthExpenses,
                'avg_monthly_income' => $avgMonthlyIncome,
                'avg_monthly_expenses' => $avgMonthlyExpenses,
                'reference_income' => $referenceMonthlyIncome,
                'daily_burn' => round($periodExpenses / $daysInPeriod, 2),
            ],
            'categories' => $categoryBreakdown,
            'concentration' => $concentration,
            'buckets' => $bucketTotals,
            'allocation_50_30_20' => $allocation503020,
            'allocation_6_jars' => $allocation6Jars,
            'recommendations' => $recommendations,
        ];
    }

    /* ------------------------------------------------------------------
     |  Category Breakdown & Bucketing
     | ------------------------------------------------------------------ */

    protected function getCategoryBreakdown(string $start, string $end, float $totalExpenses, float $totalIncome): array
    {
        $rows = $this->flowQuery()
            ->where('finance_transactions.type', 'expenses')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->leftJoin('finance_transaction_categories as c', 'finance_transactions.transaction_category_id', '=', 'c.id')
            ->select(
                'finance_transactions.transaction_category_id as id',
                DB::raw('MAX(c.name) as name'),
                DB::raw('ABS(SUM(finance_transactions.currency_amount)) as total'),
                DB::raw('COUNT(*) as tx_count')
            )
            ->groupBy('finance_transactions.transaction_category_id')
            ->orderByDesc('total')
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $amount = (float)$row->total;
            $name   = $row->name ?: 'Без категорії';
            $bucket = self::classifyCategory($name);
            $txCount = (int)$row->tx_count;

            $shareOfExpenses = $totalExpenses > 0 ? round(($amount / $totalExpenses) * 100, 1) : 0.0;
            $shareOfIncome   = $totalIncome > 0   ? round(($amount / $totalIncome) * 100, 1)   : null;

            // Burn severity
            $burnLevel = 'low';
            if ($shareOfExpenses >= 30.0) {
                $burnLevel = 'critical';
            } elseif ($shareOfExpenses >= 15.0) {
                $burnLevel = 'high';
            } elseif ($shareOfExpenses >= 7.0) {
                $burnLevel = 'moderate';
            }

            $items[] = [
                'id' => $row->id ? (int)$row->id : null,
                'name' => $name,
                'amount' => $amount,
                'tx_count' => $txCount,
                'avg_tx' => $txCount > 0 ? round($amount / $txCount, 2) : $amount,
                'share_of_expenses' => $shareOfExpenses,
                'share_of_income' => $shareOfIncome,
                'bucket' => $bucket,
                'bucket_meta' => self::getBucketMetadata($bucket),
                'burn_level' => $burnLevel,
            ];
        }

        return $items;
    }

    protected function calculateBucketTotals(array $categories, float $totalExpenses): array
    {
        $buckets = [
            self::BUCKET_NEEDS => 0.0,
            self::BUCKET_WANTS => 0.0,
            self::BUCKET_SAVINGS => 0.0,
            self::BUCKET_EDUCATION => 0.0,
            self::BUCKET_LONG_TERM => 0.0,
            self::BUCKET_GIVE => 0.0,
            self::BUCKET_OTHER => 0.0,
        ];

        $bucketCategoryLists = [
            self::BUCKET_NEEDS => [],
            self::BUCKET_WANTS => [],
            self::BUCKET_SAVINGS => [],
            self::BUCKET_EDUCATION => [],
            self::BUCKET_LONG_TERM => [],
            self::BUCKET_GIVE => [],
            self::BUCKET_OTHER => [],
        ];

        foreach ($categories as $cat) {
            $b = $cat['bucket'];
            $buckets[$b] = ($buckets[$b] ?? 0.0) + $cat['amount'];
            $bucketCategoryLists[$b][] = $cat;
        }

        $result = [];
        foreach ($buckets as $bKey => $sum) {
            $meta = self::getBucketMetadata($bKey);
            $share = $totalExpenses > 0 ? round(($sum / $totalExpenses) * 100, 1) : 0.0;
            $result[$bKey] = [
                'key' => $bKey,
                'name' => $meta['name'],
                'short_name' => $meta['short_name'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'bg' => $meta['bg'],
                'amount' => round($sum, 2),
                'share_of_expenses' => $share,
                'categories' => $bucketCategoryLists[$bKey] ?? [],
            ];
        }

        return $result;
    }

    /* ------------------------------------------------------------------
     |  Concentration & Leaks Analysis ("На що більше йдуть витрати")
     | ------------------------------------------------------------------ */

    protected function buildConcentrationAnalysis(array $categories, float $totalExpenses, float $totalIncome): array
    {
        $topCategories = array_slice($categories, 0, 8);
        $top3Sum = array_sum(array_column(array_slice($categories, 0, 3), 'amount'));
        $top5Sum = array_sum(array_column(array_slice($categories, 0, 5), 'amount'));

        $top3Share = $totalExpenses > 0 ? round(($top3Sum / $totalExpenses) * 100, 1) : 0.0;
        $top5Share = $totalExpenses > 0 ? round(($top5Sum / $totalExpenses) * 100, 1) : 0.0;

        $dominant = $categories[0] ?? null;

        // Detect potential leak points:
        // 1. Food out & Dining (restaurants, cafe, food delivery)
        $diningSum = 0.0;
        $diningCount = 0;
        foreach ($categories as $c) {
            $lower = mb_strtolower($c['name'], 'UTF-8');
            if (str_contains($lower, 'кафе') || str_contains($lower, 'ресторан') || str_contains($lower, 'фастфуд') || str_contains($lower, 'доставк')) {
                $diningSum += $c['amount'];
                $diningCount += $c['tx_count'];
            }
        }
        $diningSavings20 = round($diningSum * 0.20, 2);

        // 2. Subscriptions
        $subsSum = 0.0;
        foreach ($categories as $c) {
            $lower = mb_strtolower($c['name'], 'UTF-8');
            if (str_contains($lower, 'підписк') || str_contains($lower, 'subscription') || str_contains($lower, 'netfl') || str_contains($lower, 'spot')) {
                $subsSum += $c['amount'];
            }
        }

        // 3. Taxi
        $taxiSum = 0.0;
        foreach ($categories as $c) {
            $lower = mb_strtolower($c['name'], 'UTF-8');
            if (str_contains($lower, 'таксі') || str_contains($lower, 'uber') || str_contains($lower, 'bolt')) {
                $taxiSum += $c['amount'];
            }
        }

        return [
            'top_categories' => $topCategories,
            'top_3_sum' => round($top3Sum, 2),
            'top_3_share' => $top3Share,
            'top_5_sum' => round($top5Sum, 2),
            'top_5_share' => $top5Share,
            'dominant_category' => $dominant,
            'leaks' => [
                'dining' => [
                    'amount' => round($diningSum, 2),
                    'tx_count' => $diningCount,
                    'potential_monthly_savings' => $diningSavings20,
                    'potential_annual_savings' => round($diningSavings20 * 12, 2),
                    'share_of_income' => $totalIncome > 0 ? round(($diningSum / $totalIncome) * 100, 1) : null,
                ],
                'subscriptions' => [
                    'amount' => round($subsSum, 2),
                    'annual_amount' => round($subsSum * 12, 2),
                ],
                'taxi' => [
                    'amount' => round($taxiSum, 2),
                    'share_of_expenses' => $totalExpenses > 0 ? round(($taxiSum / $totalExpenses) * 100, 1) : 0,
                ],
            ],
        ];
    }

    /* ------------------------------------------------------------------
     |  50 / 30 / 20 Allocation Model
     | ------------------------------------------------------------------ */

    protected function build503020Allocation(float $referenceIncome, float $actualIncome, float $actualExpenses, array $buckets): array
    {
        // 50% Needs: basic needs + long term assets/repairs
        $needsActual = ($buckets[self::BUCKET_NEEDS]['amount'] ?? 0.0)
            + ($buckets[self::BUCKET_LONG_TERM]['amount'] ?? 0.0);

        // 30% Wants: lifestyle + education + gifts + other
        $wantsActual = ($buckets[self::BUCKET_WANTS]['amount'] ?? 0.0)
            + ($buckets[self::BUCKET_EDUCATION]['amount'] ?? 0.0)
            + ($buckets[self::BUCKET_GIVE]['amount'] ?? 0.0)
            + ($buckets[self::BUCKET_OTHER]['amount'] ?? 0.0);

        // 20% Savings: explicit savings transactions + net cash flow surplus
        $netCashFlow = max(0.0, $actualIncome - $actualExpenses);
        $explicitSavings = $buckets[self::BUCKET_SAVINGS]['amount'] ?? 0.0;
        $savingsActual = $explicitSavings + $netCashFlow;

        $incomeBase = max(1.0, $referenceIncome);

        $recNeeds = round($incomeBase * 0.50, 2);
        $recWants = round($incomeBase * 0.30, 2);
        $recSavings = round($incomeBase * 0.20, 2);

        $needsPct = round(($needsActual / $incomeBase) * 100, 1);
        $wantsPct = round(($wantsActual / $incomeBase) * 100, 1);
        $savingsPct = round(($savingsActual / $incomeBase) * 100, 1);

        $needsDiff = round($needsActual - $recNeeds, 2);
        $wantsDiff = round($wantsActual - $recWants, 2);
        $savingsDiff = round($savingsActual - $recSavings, 2);

        // Status determinations
        $needsStatus = $needsPct <= 50.0 ? 'optimal' : ($needsPct <= 60.0 ? 'acceptable' : 'danger');
        $wantsStatus = $wantsPct <= 30.0 ? 'optimal' : ($wantsPct <= 38.0 ? 'acceptable' : 'danger');
        $savingsStatus = $savingsPct >= 20.0 ? 'great' : ($savingsPct >= 10.0 ? 'acceptable' : 'danger');

        // Overall balance diagnosis
        $overallStatus = 'optimal';
        $statusBadge = 'Збалансований бюджет';
        $statusColor = '#10b981';

        if ($actualIncome > 0 && $actualExpenses > $actualIncome) {
            $overallStatus = 'deficit';
            $statusBadge = 'Дефіцит бюджету';
            $statusColor = '#ef4444';
        } elseif ($wantsStatus === 'danger') {
            $overallStatus = 'wants_heavy';
            $statusBadge = 'Перевитрата на бажання';
            $statusColor = '#f59e0b';
        } elseif ($needsStatus === 'danger') {
            $overallStatus = 'needs_heavy';
            $statusBadge = 'Високе базове навантаження';
            $statusColor = '#f97316';
        } elseif ($savingsStatus === 'danger') {
            $overallStatus = 'low_savings';
            $statusBadge = 'Низькі заощадження';
            $statusColor = '#eab308';
        }

        return [
            'income_base' => $incomeBase,
            'overall_status' => $overallStatus,
            'status_badge' => $statusBadge,
            'status_color' => $statusColor,
            'needs' => [
                'name' => 'Обов\'язкові потреби',
                'target_pct' => 50,
                'target_amount' => $recNeeds,
                'actual_amount' => round($needsActual, 2),
                'actual_pct' => $needsPct,
                'diff' => $needsDiff,
                'status' => $needsStatus,
                'icon' => '🏠',
                'color' => '#3b82f6',
                'description' => 'Житло, комуналка, базове харчування, транспорт, здоров\'я, зв\'язок',
            ],
            'wants' => [
                'name' => 'Бажання та стиль життя',
                'target_pct' => 30,
                'target_amount' => $recWants,
                'actual_amount' => round($wantsActual, 2),
                'actual_pct' => $wantsPct,
                'diff' => $wantsDiff,
                'status' => $wantsStatus,
                'icon' => '☕',
                'color' => '#f59e0b',
                'description' => 'Кафе, розваги, одяг, підписки, таксі, свята, хобі',
            ],
            'savings' => [
                'name' => 'Заощадження та інвестиції',
                'target_pct' => 20,
                'target_amount' => $recSavings,
                'actual_amount' => round($savingsActual, 2),
                'actual_pct' => $savingsPct,
                'diff' => $savingsDiff,
                'status' => $savingsStatus,
                'icon' => '🛡️',
                'color' => '#10b981',
                'description' => 'Фінансова подушка, депозити, інвестиції, закриття боргів',
            ],
        ];
    }

    /* ------------------------------------------------------------------
     |  6 Jars Allocation Model (T. Harv Eker)
     | ------------------------------------------------------------------ */

    protected function build6JarsAllocation(float $referenceIncome, array $buckets): array
    {
        $incomeBase = max(1.0, $referenceIncome);

        $jars = [
            [
                'code' => 'NEC',
                'name' => 'Необхідні витрати (Necessities)',
                'percent' => 55,
                'icon' => '🏠',
                'color' => '#3b82f6',
                'bg' => '#eff6ff',
                'target_amount' => round($incomeBase * 0.55, 2),
                'actual_amount' => round($buckets[self::BUCKET_NEEDS]['amount'] ?? 0.0, 2),
                'desc' => 'Оренда, комуналка, їжа, транспорт, ліки',
            ],
            [
                'code' => 'FFA',
                'name' => 'Фінансова свобода (Financial Freedom)',
                'percent' => 10,
                'icon' => '📈',
                'color' => '#10b981',
                'bg' => '#f0fdf4',
                'target_amount' => round($incomeBase * 0.10, 2),
                'actual_amount' => round($buckets[self::BUCKET_SAVINGS]['amount'] ?? 0.0, 2),
                'desc' => 'Інвестиції, пасивний дохід, криптовалюта, акції',
            ],
            [
                'code' => 'LTSS',
                'name' => 'Довгострокові заощадження (LTSS)',
                'percent' => 10,
                'icon' => '🛠️',
                'color' => '#06b6d4',
                'bg' => '#ecfeff',
                'target_amount' => round($incomeBase * 0.10, 2),
                'actual_amount' => round($buckets[self::BUCKET_LONG_TERM]['amount'] ?? 0.0, 2),
                'desc' => 'Резерв на ремонт, великі покупки, авто',
            ],
            [
                'code' => 'EDU',
                'name' => 'Освіта та розвиток (Education)',
                'percent' => 10,
                'icon' => '📚',
                'color' => '#8b5cf6',
                'bg' => '#f5f3ff',
                'target_amount' => round($incomeBase * 0.10, 2),
                'actual_amount' => round($buckets[self::BUCKET_EDUCATION]['amount'] ?? 0.0, 2),
                'desc' => 'Курси, книги, розвиток власних навичок',
            ],
            [
                'code' => 'PLAY',
                'name' => 'Розваги та насолода (Play)',
                'percent' => 10,
                'icon' => '🎉',
                'color' => '#f59e0b',
                'bg' => '#fffbeb',
                'target_amount' => round($incomeBase * 0.10, 2),
                'actual_amount' => round($buckets[self::BUCKET_WANTS]['amount'] ?? 0.0, 2),
                'desc' => 'Кафе, кіно, відпочинок, емоції без почуття провини',
            ],
            [
                'code' => 'GIVE',
                'name' => 'Благодійність і подарунки (Give)',
                'percent' => 5,
                'icon' => '🎁',
                'color' => '#ec4899',
                'bg' => '#fdf2f8',
                'target_amount' => round($incomeBase * 0.05, 2),
                'actual_amount' => round($buckets[self::BUCKET_GIVE]['amount'] ?? 0.0, 2),
                'desc' => 'Донати, допомога іншим, подарунки',
            ],
        ];

        return [
            'income_base' => $incomeBase,
            'jars' => $jars,
        ];
    }

    /* ------------------------------------------------------------------
     |  Smart Recommendations Engine
     | ------------------------------------------------------------------ */

    protected function generateRecommendations(
        float $periodIncome,
        float $periodExpenses,
        float $referenceIncome,
        array $allocation,
        array $concentration,
        float $avgExpenses,
        Carbon $now
    ): array {
        $tips = [];
        $fmt = fn ($v) => number_format((float)$v, 0, '.', ' ') . ' ₴';

        // 1. Health and Flow diagnosis
        $net = $periodIncome - $periodExpenses;
        $savingsRate = $periodIncome > 0 ? round(($net / $periodIncome) * 100, 1) : 0.0;

        if ($periodIncome > 0 && $net < 0) {
            $tips[] = [
                'type' => 'danger',
                'icon' => '🚨',
                'title' => 'Увага: витрати перевищують дохід',
                'text' => "За поточний період витрати перевищили надходження на {$fmt(abs($net))}. Рекомендується тимчасово заморозити непершочергові статті витрат (кафе, розваги, шопінг) для стабілізації касового розриву.",
                'badge' => 'Дефіцитний період',
            ];
        } elseif ($savingsRate >= 20.0) {
            $tips[] = [
                'type' => 'success',
                'icon' => '🏆',
                'title' => "Відмінний рівень заощаджень: {$savingsRate}%",
                'text' => "Ви зберігаєте понад рекомендовану норму у 20% від доходу. Чистий профіцит за період складає {$fmt($net)}. Рекомендовано направити цей надлишок у формування фінансової подушки або дохідні депозити/інвестиції.",
                'badge' => 'Збалансований бюджет',
            ];
        } else {
            $tips[] = [
                'type' => 'warning',
                'icon' => '💡',
                'title' => 'Збільшення норми заощаджень',
                'text' => "Поточний темп заощаджень складає {$savingsRate}% (рекомендовано від 20%). Щоб вийти на цільові 20% при вашому доході, потрібно відкладати щонайменше {$fmt($referenceIncome * 0.20)} щомісяця.",
                'badge' => 'Зона росту',
            ];
        }

        // 2. Dominant Expense Category Tip ("На що більше йдуть витрати")
        $dominant = $concentration['dominant_category'] ?? null;
        if ($dominant && $dominant['share_of_expenses'] >= 20.0) {
            $catName = $dominant['name'];
            $catAmount = $fmt($dominant['amount']);
            $catShare = $dominant['share_of_expenses'];

            $tips[] = [
                'type' => 'info',
                'icon' => '🔍',
                'title' => "Головний центр витрат: «{$catName}» ({$catShare}%)",
                'text' => "Найбільша частка ваших коштів іде на «{$catName}» — {$catAmount} ({$catShare}% усіх витрат за період). Встановіть місячний ліміт на рівні {$fmt($dominant['amount'] * 0.9)}, щоб утримати цю категорію під повним контролем.",
                'badge' => 'Топ-витрата',
            ];
        }

        // 3. 50/30/20 Specific Distribution Advice
        $recNeeds = $fmt($allocation['needs']['target_amount']);
        $recWants = $fmt($allocation['wants']['target_amount']);
        $recSavings = $fmt($allocation['savings']['target_amount']);
        $refIncFmt = $fmt($referenceIncome);

        $tips[] = [
            'type' => 'primary',
            'icon' => '🎯',
            'title' => "Формула розподілу доходу ({$refIncFmt} / місяць)",
            'text' => "Для стабільного зростання капіталу розподіляйте кожен місячний дохід за правилом трьох кошиків:\n" .
                      "• 🏠 50% ({$recNeeds}) — Базові потреби (житло, їжа, комуналка, транспорт)\n" .
                      "• ☕ 30% ({$recWants}) — Стиль життя та бажання (розваги, кафе, покупки)\n" .
                      "• 🛡️ 20% ({$recSavings}) — Заощадження (відкладати одразу в день надходження)",
            'badge' => 'Правило 50/30/20',
        ];

        // 4. Dining & Lifestyle Optimization Leak Check
        $dining = $concentration['leaks']['dining'] ?? null;
        if ($dining && $dining['amount'] > 1000) {
            $dinAmt = $fmt($dining['amount']);
            $dinSaveMonth = $fmt($dining['potential_monthly_savings']);
            $dinSaveYear = $fmt($dining['potential_annual_savings']);

            $tips[] = [
                'type' => 'warning',
                'icon' => '✂️',
                'title' => "Потенціал економії на кафе та замовленні їжі",
                'text' => "Витрати на кафе, ресторани та доставку склали {$dinAmt} за {$dining['tx_count']} транзакцій. Якщо скоротити відвідування кафе лише на 20%, ви зможете зберігати додатково {$dinSaveMonth} щомісяця ({$dinSaveYear} на рік!).",
                'badge' => 'Швидка оптимізація',
            ];
        }

        // 5. Practical Paycheck Day Checklist
        $tips[] = [
            'type' => 'success',
            'icon' => '📝',
            'title' => 'Покроковий алгоритм у день надходження коштів',
            'text' => "1. Спочатку заплатіть собі: одразу перерахуйте 20% ({$recSavings}) на депозит чи окремий ощадний рахунок.\n" .
                      "2. Оплатіть обов'язкові платежі: комуналку, інтернет, зв'язок, кредити.\n" .
                      "3. Залишок на життя розділіть порівну на 4 тижні (по ≈ {$fmt($allocation['wants']['target_amount'] / 4)} на тиждень), щоб уникнути дефіциту в кінці місяця.",
            'badge' => 'Чек-лист',
        ];

        return $tips;
    }

    /**
     * Plan current month's budget based on the actual income of the PREVIOUS month ("що куди").
     * Zero-based budgeting / YNAB principle: spend and save this month using money earned last month.
     */
    public function monthlyBudgetFromPreviousMonth(?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();
        $prev = $now->copy()->subMonth();

        $curStart = $now->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $curEnd   = $now->copy()->endOfMonth()->format('Y-m-d H:i:s');
        $todayEnd = $now->copy()->endOfDay()->format('Y-m-d H:i:s');

        $prevStart = $prev->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $prevEnd   = $prev->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $curMonthName  = FinanceInsightsService::UKR_MONTHS[(int)$now->month] . ' ' . $now->year;
        $prevMonthName = FinanceInsightsService::UKR_MONTHS[(int)$prev->month] . ' ' . $prev->year;

        // 1. Income received in the previous month (the resource base for this month)
        $prevIncome = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'income')
            ->whereBetween('finance_transactions.created_at', [$prevStart, $prevEnd])
            ->sum('finance_transactions.currency_amount'));

        $isFallback = false;
        if ($prevIncome < 1.0) {
            // Fallback: average monthly income or current month income
            $histStart = $now->copy()->startOfMonth()->subMonths(3)->format('Y-m-d H:i:s');
            $histEnd = $now->copy()->startOfMonth()->subSecond()->format('Y-m-d H:i:s');
            $histIncome = abs((float)$this->flowQuery()
                ->where('finance_transactions.type', 'income')
                ->whereBetween('finance_transactions.created_at', [$histStart, $histEnd])
                ->sum('finance_transactions.currency_amount'));
            $histMonths = max(1, (int)$this->flowQuery()
                ->where('finance_transactions.type', 'expenses')
                ->whereBetween('finance_transactions.created_at', [$histStart, $histEnd])
                ->selectRaw("COUNT(DISTINCT DATE_FORMAT(finance_transactions.created_at, '%Y-%m')) as c")
                ->value('c'));
            $avgInc = round($histIncome / $histMonths, 2);

            $curIncome = abs((float)$this->flowQuery()
                ->where('finance_transactions.type', 'income')
                ->whereBetween('finance_transactions.created_at', [$curStart, $todayEnd])
                ->sum('finance_transactions.currency_amount'));

            $prevIncome = $avgInc > 0 ? $avgInc : ($curIncome > 0 ? $curIncome : 50000.0);
            $isFallback = true;
        }

        // 2. Current month actual expenses so far
        $curSpent = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'expenses')
            ->whereBetween('finance_transactions.created_at', [$curStart, $todayEnd])
            ->sum('finance_transactions.currency_amount'));

        $curIncome = abs((float)$this->flowQuery()
            ->where('finance_transactions.type', 'income')
            ->whereBetween('finance_transactions.created_at', [$curStart, $todayEnd])
            ->sum('finance_transactions.currency_amount'));

        // Current month category breakdown
        $categories = $this->getCategoryBreakdown($curStart, $todayEnd, $curSpent, $curIncome);
        $buckets = $this->calculateBucketTotals($categories, $curSpent);

        // Days progress
        $daysTotal   = (int)$now->daysInMonth;
        $daysElapsed = (int)$now->day;
        $daysLeft    = max(0, $daysTotal - $daysElapsed);
        $progressPct = round(($daysElapsed / max(1, $daysTotal)) * 100, 1);

        // 3. 50 / 30 / 20 Plan for Current Month from Last Month's Income
        $allocNeeds = round($prevIncome * 0.50, 2);
        $allocWants = round($prevIncome * 0.30, 2);
        $allocSavings = round($prevIncome * 0.20, 2);

        $spentNeeds = round(($buckets[self::BUCKET_NEEDS]['amount'] ?? 0.0) + ($buckets[self::BUCKET_LONG_TERM]['amount'] ?? 0.0), 2);
        $spentWants = round(
            ($buckets[self::BUCKET_WANTS]['amount'] ?? 0.0)
            + ($buckets[self::BUCKET_EDUCATION]['amount'] ?? 0.0)
            + ($buckets[self::BUCKET_GIVE]['amount'] ?? 0.0)
            + ($buckets[self::BUCKET_OTHER]['amount'] ?? 0.0),
            2
        );

        $explicitSavings = round($buckets[self::BUCKET_SAVINGS]['amount'] ?? 0.0, 2);
        $currentSurplus = max(0.0, $curIncome - $curSpent);
        $savedSoFar = round($explicitSavings + $currentSurplus, 2);

        $remNeeds = round($allocNeeds - $spentNeeds, 2);
        $remWants = round($allocWants - $spentWants, 2);
        $remSavings = round($allocSavings - $savedSoFar, 2);

        $pctNeeds = $allocNeeds > 0 ? round(($spentNeeds / $allocNeeds) * 100, 1) : 0.0;
        $pctWants = $allocWants > 0 ? round(($spentWants / $allocWants) * 100, 1) : 0.0;
        $pctSavings = $allocSavings > 0 ? round(($savedSoFar / $allocSavings) * 100, 1) : 0.0;

        $safeNeedsDaily = $daysLeft > 0 ? max(0.0, round($remNeeds / $daysLeft, 2)) : 0.0;
        $safeWantsDaily = $daysLeft > 0 ? max(0.0, round($remWants / $daysLeft, 2)) : 0.0;
        $weeksLeft = max(1, ceil($daysLeft / 7));
        $safeWantsWeekly = max(0.0, round($remWants / $weeksLeft, 2));

        // 4. 6 Jars Allocation from Previous Month Income
        $jarsAlloc = [
            'NEC' => [
                'code' => 'NEC', 'name' => 'Обов\'язкове (55%)', 'icon' => '🏠', 'color' => '#3b82f6', 'bg' => '#eff6ff',
                'allocated' => round($prevIncome * 0.55, 2),
                'spent' => round($buckets[self::BUCKET_NEEDS]['amount'] ?? 0.0, 2),
                'desc' => 'Оренда, комуналка, їжа, транспорт',
            ],
            'FFA' => [
                'code' => 'FFA', 'name' => 'Інвестиції (10%)', 'icon' => '📈', 'color' => '#10b981', 'bg' => '#f0fdf4',
                'allocated' => round($prevIncome * 0.10, 2),
                'spent' => round($buckets[self::BUCKET_SAVINGS]['amount'] ?? 0.0, 2),
                'desc' => 'Капітал, депозити, акції',
            ],
            'LTSS' => [
                'code' => 'LTSS', 'name' => 'Резерв (10%)', 'icon' => '🛠️', 'color' => '#06b6d4', 'bg' => '#ecfeff',
                'allocated' => round($prevIncome * 0.10, 2),
                'spent' => round($buckets[self::BUCKET_LONG_TERM]['amount'] ?? 0.0, 2),
                'desc' => 'Ремонт, техніка, авто',
            ],
            'EDU' => [
                'code' => 'EDU', 'name' => 'Освіта (10%)', 'icon' => '📚', 'color' => '#8b5cf6', 'bg' => '#f5f3ff',
                'allocated' => round($prevIncome * 0.10, 2),
                'spent' => round($buckets[self::BUCKET_EDUCATION]['amount'] ?? 0.0, 2),
                'desc' => 'Курси, книги, розвиток',
            ],
            'PLAY' => [
                'code' => 'PLAY', 'name' => 'Розваги (10%)', 'icon' => '🎉', 'color' => '#f59e0b', 'bg' => '#fffbeb',
                'allocated' => round($prevIncome * 0.10, 2),
                'spent' => round($buckets[self::BUCKET_WANTS]['amount'] ?? 0.0, 2),
                'desc' => 'Кафе, відпочинок, емоції',
            ],
            'GIVE' => [
                'code' => 'GIVE', 'name' => 'Благодійність (5%)', 'icon' => '🎁', 'color' => '#ec4899', 'bg' => '#fdf2f8',
                'allocated' => round($prevIncome * 0.05, 2),
                'spent' => round($buckets[self::BUCKET_GIVE]['amount'] ?? 0.0, 2),
                'desc' => 'Донати, допомога, подарунки',
            ],
        ];

        foreach ($jarsAlloc as &$j) {
            $j['remaining'] = round($j['allocated'] - $j['spent'], 2);
            $j['percent_used'] = $j['allocated'] > 0 ? round(($j['spent'] / $j['allocated']) * 100, 1) : 0.0;
        }
        unset($j);

        // 5. "Що куди" concrete step-by-step envelopes & recommendations
        $fmt = fn ($v) => number_format((float)$v, 0, '.', ' ') . ' ₴';

        $envelopes = [
            [
                'step' => 1,
                'title' => 'Обов\'язкові потреби (50%)',
                'amount' => $allocNeeds,
                'spent' => $spentNeeds,
                'remaining' => $remNeeds,
                'icon' => '🏠',
                'color' => '#3b82f6',
                'bg' => '#eff6ff',
                'destination' => 'Основний банківський рахунок або конверт «Обов\'язкове»',
                'note' => "Виділено {$fmt($allocNeeds)}. Витрачено {$fmt($spentNeeds)}. Залишок до кінця місяця: {$fmt($remNeeds)} (можна витрачати до {$fmt($safeNeedsDaily)}/день).",
            ],
            [
                'step' => 2,
                'title' => 'Бажання та стиль життя (30%)',
                'amount' => $allocWants,
                'spent' => $spentWants,
                'remaining' => $remWants,
                'icon' => '☕',
                'color' => '#f59e0b',
                'bg' => '#fffbeb',
                'destination' => 'Окрема повсякденна карта (Mono, Приват)',
                'note' => "Виділено {$fmt($allocWants)}. Витрачено {$fmt($spentWants)}. Залишок: {$fmt($remWants)} — безпечний ліміт: {$fmt($safeWantsWeekly)}/тиждень ({$fmt($safeWantsDaily)}/день).",
            ],
            [
                'step' => 3,
                'title' => 'Заощадження та інвестиції (20%)',
                'amount' => $allocSavings,
                'spent' => $savedSoFar,
                'remaining' => $remSavings,
                'icon' => '🛡️',
                'color' => '#10b981',
                'bg' => '#f0fdf4',
                'destination' => 'Депозит, інвест-рахунок або подушка безпеки',
                'note' => "Ціль накопичень з минулого доходу: {$fmt($allocSavings)}. Вже збережено: {$fmt($savedSoFar)}. " . ($remSavings <= 0 ? "Ціль повністю досягнута! 🚀" : "Залишилось відкласти {$fmt($remSavings)}."),
            ],
        ];

        return [
            'current_month' => [
                'name' => $curMonthName,
                'days_total' => $daysTotal,
                'days_elapsed' => $daysElapsed,
                'days_left' => $daysLeft,
                'progress_percent' => $progressPct,
                'spent_so_far' => $curSpent,
                'income_so_far' => $curIncome,
            ],
            'prev_month' => [
                'name' => $prevMonthName,
                'income' => $prevIncome,
                'is_fallback' => $isFallback,
            ],
            'plan_50_30_20' => [
                'needs' => [
                    'allocated' => $allocNeeds,
                    'spent' => $spentNeeds,
                    'remaining' => $remNeeds,
                    'percent_used' => $pctNeeds,
                    'safe_daily' => $safeNeedsDaily,
                    'status' => $spentNeeds <= $allocNeeds ? 'ok' : 'over',
                ],
                'wants' => [
                    'allocated' => $allocWants,
                    'spent' => $spentWants,
                    'remaining' => $remWants,
                    'percent_used' => $pctWants,
                    'safe_weekly' => $safeWantsWeekly,
                    'safe_daily' => $safeWantsDaily,
                    'status' => $spentWants <= $allocWants ? 'ok' : 'over',
                ],
                'savings' => [
                    'allocated' => $allocSavings,
                    'saved' => $savedSoFar,
                    'remaining' => $remSavings,
                    'percent_used' => $pctSavings,
                    'status' => $savedSoFar >= $allocSavings ? 'great' : 'pending',
                ],
            ],
            'plan_6_jars' => $jarsAlloc,
            'envelopes' => $envelopes,
            'top_categories_this_month' => array_slice($categories, 0, 5),
        ];
    }
}

