<?php

namespace App\Orchid\Screens\Analytic\Finance;

use App\Orchid\Layouts\Analytic\Finance\AnalyticFinanceSelection;
use App\Orchid\Layouts\Finance\Transaction\Charts\ChartBarTransaction;
use App\Orchid\Layouts\Finance\Transaction\Charts\ChartPieTransaction;
use App\Orchid\Layouts\Finance\Transaction\Charts\ChartPieTransactionLayout;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
use App\Services\Finance\Analytic\FinanceInsightsService;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;

use App\Services\Finance\Transaction\TransactionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class AnalyticFinanceScreen extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        $createdAt = $request->get('created_at', []);
        $start = !empty($createdAt['start'])
            ? (strlen($createdAt['start']) === 10 ? Carbon::parse($createdAt['start'])->startOfDay()->format('Y-m-d H:i:s') : $createdAt['start'])
            : Carbon::now()->subYear()->startOfDay()->format('Y-m-d H:i:s');
        $end = !empty($createdAt['end'])
            ? (strlen($createdAt['end']) === 10 ? Carbon::parse($createdAt['end'])->endOfDay()->format('Y-m-d H:i:s') : $createdAt['end'])
            : Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        $startDate = Carbon::parse($start);
        $endDate   = Carbon::parse($end);

        // Calculate previous period of equal length
        if ($startDate->isSameDay($startDate->copy()->startOfMonth()) && $endDate->isSameDay($endDate->copy()->endOfMonth())) {
            $prevStart = $startDate->copy()->subMonth()->startOfMonth()->format('Y-m-d H:i:s');
            $prevEnd   = $startDate->copy()->subMonth()->endOfMonth()->format('Y-m-d H:i:s');
        } elseif ($startDate->isSameDay($startDate->copy()->startOfYear()) && $endDate->isSameDay($endDate->copy()->endOfYear())) {
            $prevStart = $startDate->copy()->subYear()->startOfYear()->format('Y-m-d H:i:s');
            $prevEnd   = $startDate->copy()->subYear()->endOfYear()->format('Y-m-d H:i:s');
        } else {
            $diffDays = max(1, $startDate->diffInDays($endDate) + 1);
            $prevEndDateEnd = $startDate->copy()->subSecond();
            $prevStartDate = $prevEndDateEnd->copy()->subDays($diffDays - 1)->startOfDay();
            $prevStart = $prevStartDate->format('Y-m-d H:i:s');
            $prevEnd   = $prevEndDateEnd->format('Y-m-d H:i:s');
        }

        $prevStartDateObj = Carbon::parse($prevStart);
        $prevEndDateObj   = Carbon::parse($prevEnd);

        $billId = $request->get('bill_id');
        $billId = (!empty($billId) && is_numeric($billId)) ? (int)$billId : null;

        $categoryId = $request->get('category_id') ?? $request->get('transaction_category_id');
        $categoryId = (!empty($categoryId) && is_numeric($categoryId)) ? (int)$categoryId : null;

        $transaction = new TransactionsService();
        $transactionIncome = new TransactionIncomeService();
        $transactionExpenses = new TransactionExpensesService();

        if ($billId) {
            $transaction->setBillId($billId);
            $transactionIncome->setBillId($billId);
            $transactionExpenses->setBillId($billId);
        }

        // Current period sums
        $incomeCurrent = (float)$transactionIncome->getSum('currency_amount', false, null, $start, $end, $billId);
        $expensesCurrent = abs((float)$transactionExpenses->getSum('currency_amount', false, null, $start, $end, $billId));
        $balanceCurrent = (float)$transaction->getTotalAmount('currency_amount', false, null, $start, $end, $billId);

        // Previous period sums
        $incomePrev = (float)$transactionIncome->getSum('currency_amount', false, null, $prevStart, $prevEnd, $billId);
        $expensesPrev = abs((float)$transactionExpenses->getSum('currency_amount', false, null, $prevStart, $prevEnd, $billId));
        $balancePrev = (float)$transaction->getTotalAmount('currency_amount', false, null, $prevStart, $prevEnd, $billId);

        // Transaction counts and stats
        $userId = $transaction->getUserId();
        $incomeCountQ = \App\Models\FinanceTransaction::where('user_id', $userId)
            ->where('is_balance', 1)
            ->where('type', 'income')
            ->whereBetween('created_at', [$start, $end]);
        if ($billId) {
            $incomeCountQ->where('finance_bill_id', $billId);
        }
        $incomeCount = $incomeCountQ->count();

        $expensesCountQ = \App\Models\FinanceTransaction::where('user_id', $userId)
            ->where('is_balance', 1)
            ->where('type', 'expenses')
            ->whereBetween('created_at', [$start, $end]);
        if ($billId) {
            $expensesCountQ->where('finance_bill_id', $billId);
        }
        $expensesCount = $expensesCountQ->count();

        $daysInPeriod = max(1, $startDate->diffInDays($endDate) + 1);
        $dailyBurnRate = round($expensesCurrent / $daysInPeriod, 2);
        $savingsRate = $incomeCurrent > 0 ? round((($incomeCurrent - $expensesCurrent) / $incomeCurrent) * 100, 1) : 0;
        $avgIncomeTx = $incomeCount > 0 ? round($incomeCurrent / $incomeCount, 2) : 0;
        $avgExpenseTx = $expensesCount > 0 ? round($expensesCurrent / $expensesCount, 2) : 0;

        $kpi = [
            'period' => [
                'start' => $startDate->format('d.m.Y'),
                'end' => $endDate->format('d.m.Y'),
                'days' => $daysInPeriod,
                'prev_start' => $prevStartDateObj->format('d.m.Y'),
                'prev_end' => $prevEndDateObj->format('d.m.Y'),
            ],
            'balance' => [
                'current' => $balanceCurrent,
                'prev' => $balancePrev,
                'trend' => $this->calculateTrend($balanceCurrent, $balancePrev, 'balance'),
                'savings_rate' => $savingsRate,
            ],
            'income' => [
                'current' => $incomeCurrent,
                'prev' => $incomePrev,
                'trend' => $this->calculateTrend($incomeCurrent, $incomePrev, 'income'),
                'count' => $incomeCount,
                'avg' => $avgIncomeTx,
            ],
            'expenses' => [
                'current' => $expensesCurrent,
                'prev' => $expensesPrev,
                'trend' => $this->calculateTrend($expensesCurrent, $expensesPrev, 'expenses'),
                'count' => $expensesCount,
                'avg' => $avgExpenseTx,
                'daily_burn' => $dailyBurnRate,
            ],
        ];

        $data['kpi'] = $kpi;
        $data['metrics']['sum']['income'] = \App\Services\Currency\Currency::convertValueToCurrency($incomeCurrent);
        $data['metrics']['sum']['expenses'] = \App\Services\Currency\Currency::convertValueToCurrency(-1 * $expensesCurrent);
        $data['metrics']['sum']['total'] = \App\Services\Currency\Currency::convertValueToCurrency($balanceCurrent);

        $data['charts']['transactions'][] = $transactionIncome->chartBar(__("Income"), $start, $end, 'created_at', 'currency_amount', $billId);
        $data['charts']['transactions'][] = $transactionExpenses->chartBar(__("Expenses"), $start, $end, 'created_at', 'absolute_currency_amount', $billId);
        $data['charts']['transactions'][] = $transaction->chartBarBalance(__("Balance"), $start, $end, 'created_at', 'currency_amount', $billId);
        
        $data['charts']['income_year'] = $transactionIncome->chartBarComparison(__('Current Year'), __('Previous Year'), $start, $end, 'created_at', 'currency_amount', $billId);
        $data['charts']['capital'] = $transaction->chartCapital(__('Capital'), $start, $end, 'created_at', $billId);
        
        $data['charts']['categories']['income'] = $transactionIncome->chartPieCategory($start, $end, __('Income'), $billId);
        $data['charts']['categories']['expenses'] = $transactionExpenses->chartPieCategory($start, $end, '', $billId);
        
        $data['charts']['income']['bill'] = $transactionIncome->chartPieBill($start, $end, '', $billId);
        $data['charts']['income']['customer'] = $transactionIncome->chartPieCustomer($start, $end, '', $billId);
        $data['charts']['expenses']['bill'] = $transactionExpenses->chartPieBill($start, $end, '', $billId);
        $data['charts']['expenses']['source'] = $transactionExpenses->chartPieSource($start, $end, '', $billId);
        $data['top_sources_monthly'] = $transactionExpenses->getTopSourcesMonthlyExpenses($start, $end, $billId, 5, 6);

        $insights = new FinanceInsightsService($userId, $billId);
        $data['insights'] = [
            'forecast' => $insights->monthForecast(),
            'fund' => $insights->emergencyFund(),
            'category_changes' => [
                'expenses' => $insights->categoryChanges('expenses', $start, $end, $prevStart, $prevEnd),
                'income' => $insights->categoryChanges('income', $start, $end, $prevStart, $prevEnd),
            ],
        ];

        $data['transactions'] = $transaction->list(AnalyticFinanceSelection::class, $start, $end, $billId, $categoryId);

        $bills = \App\Models\FinanceBill::where('user_id', $userId)->with('currency')->orderBy('name')->get();
        $selectedBill = $billId ? $bills->firstWhere('id', $billId) : null;
        $selectedCategory = $categoryId ? \App\Models\FinanceTransactionCategory::find($categoryId) : null;

        $data['bills'] = $bills;
        $data['selected_bill_id'] = $billId;
        $data['selected_bill'] = $selectedBill;
        $data['selected_category_id'] = $categoryId;
        $data['selected_category'] = $selectedCategory;

        return $data;
    }

    /**
     * Calculate percentage trend between current and previous values.
     */
    protected function calculateTrend(float $current, float $prev, string $type = 'income'): array
    {
        $diff = $current - $prev;
        if (abs($prev) < 0.001) {
            $percent = $current > 0 ? 100.0 : ($current < 0 ? -100.0 : 0.0);
        } else {
            $percent = round(($diff / abs($prev)) * 100, 1);
        }

        if ($type === 'expenses') {
            $isFavorable = ($diff <= 0);
        } else {
            $isFavorable = ($diff >= 0);
        }

        return [
            'diff' => $diff,
            'percent' => $percent,
            'is_favorable' => $isFavorable,
            'formatted_diff' => number_format(abs($diff), 0, '.', ' ') . ' ₴',
            'formatted_prev' => number_format($prev, 0, '.', ' ') . ' ₴',
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Analytic finance';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::view('components.date-range-picker'),
            Layout::view('analytic.finance.metrics'),
            Layout::view('analytic.finance.insights'),
            Layout::view('dashboard.category-list'),
            \App\Orchid\Layouts\Dashboard\DashboardChartIncomeLayout::make('charts.income_year', __('Income for the year')),
            \App\Orchid\Layouts\Dashboard\DashboardChartCapitalLayout::make('charts.capital', __('Capital Statistics')),
            ChartBarTransaction::make('charts.transactions', __('Statistics for the year')),

            Layout::view('analytic.income-lists'),
            Layout::view('analytic.expense-lists'),
            Layout::accordion([
                'Транзакції' =>  Layout::columns([
                     TransactionListLayout::class,
                ])
            ])
        ];
    }
}
