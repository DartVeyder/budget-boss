<?php

namespace App\Orchid\Screens;

use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Dashboard\DashboardChartTransactionCategoryLayout;
use App\Orchid\Layouts\Dashboard\DashboardChartTransactionLayout;
use App\Orchid\Layouts\Dashboard\DashboardChartIncomeLayout;
use App\Orchid\Layouts\Dashboard\DashboardChartCapitalLayout;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
use App\Services\Currency\Currency;
use App\Services\Finance\Bill\BillService;
use App\Services\Finance\Crypto\Binance\BinanceService;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use App\Services\Finance\Transaction\TransactionsService;
use App\Services\Metrics\Chartable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DashboardScreen extends Screen
{
    use BillService;
    use Chartable;

    private int $userId;
    private ?int $filterUserId = null;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransaction $transactions, Request $request): iterable
    {
        Currency::updateExchangeRates(["USD", "EUR"]);
        $start = Carbon::now()->subYear();
        $end = Carbon::now();

        $user = Auth::user();
        $this->userId = $user->id;
        $currentMonth = Carbon::now()->month;

        $filterUserId = $request->get('user_id') ? (int)$request->get('user_id') : null;
        $allSharedIds = $user->getSharedUserIds();
        if ($filterUserId && !in_array($filterUserId, $allSharedIds, true)) {
            $filterUserId = null;
        }
        $this->filterUserId = $filterUserId;
        $scopedUserIds = $filterUserId ? [$filterUserId] : $allSharedIds;

        $data = [];

        $income = FinanceTransaction::where('is_balance', 1)->where('type', 'income')->whereIn('user_id', $scopedUserIds);
        $expenses = FinanceTransaction::where('is_balance', 1)->where('type', 'expenses')->whereIn('user_id', $scopedUserIds);

        $transaction = new TransactionsService();
        $transactionIncome = new TransactionIncomeService();
        $transactionExpenses = new TransactionExpensesService();

        if ($filterUserId) {
            $transaction->setTargetUserId($filterUserId);
            $transactionIncome->setTargetUserId($filterUserId);
            $transactionExpenses->setTargetUserId($filterUserId);
        }

        $data['metrics']['total']['balance'] = Currency::convertValueToCurrency($transaction->getTotalBalance());
        $data['metrics']['currentMonth']['income'] = Currency::convertValueToCurrency((clone $income)->whereMonth('created_at', $currentMonth)->whereYear('created_at', Carbon::now()->year)->sum('currency_amount'));
        $data['metrics']['currentMonth']['expenses'] = Currency::convertValueToCurrency((clone $expenses)->whereMonth('created_at', $currentMonth)->whereYear('created_at', Carbon::now()->year)->sum('currency_amount'));

        $data['metrics']['currentYear']['income'] = Currency::convertValueToCurrency((clone $income)->whereYear('created_at', Carbon::now()->year)->sum('currency_amount'));
        $data['metrics']['currentYear']['expenses'] = Currency::convertValueToCurrency((clone $expenses)->whereYear('created_at', Carbon::now()->year)->sum('currency_amount'));

        $data['metrics']['bills'] = $this->generateMetricsToBill(4, $filterUserId);
        $data['charts']['transactions'][] = $transactionIncome->chartBar(__("Income"), $start, $end, 'created_at', 'currency_amount');
        $data['charts']['transactions'][] = $transactionExpenses->chartBar(__("Expenses"), $start, $end, 'created_at', 'absolute_currency_amount');
        $data['charts']['transactions'][] = $transaction->chartBarBalance(__("Balance"), $start, $end, 'created_at', 'currency_amount');
        
        $data['charts']['income_year'] = $transactionIncome->chartBarComparison(__('Current Year'), __('Previous Year'), $start, $end, 'created_at', 'currency_amount');
        $data['charts']['capital'] = $transaction->chartCapital(__('Capital'), $start, $end, 'created_at');
        
        $data['charts']['categories']['income'] = $transactionIncome->chartPieCategory(Carbon::now()->startOfMonth(), $end);
        $data['charts']['categories']['expenses'] = $transactionExpenses->chartPieCategory(Carbon::now()->startOfMonth(), $end);
        $data['charts']['bills']['income'] = $transactionIncome->chartPieBill(Carbon::now()->startOfMonth(), $end);
        $data['charts']['bills']['expenses'] = $transactionExpenses->chartPieBill(Carbon::now()->startOfMonth(), $end);
        $data['charts']['sources']['expenses'] = $transactionExpenses->chartPieSource(Carbon::now()->startOfMonth(), $end);

        $data['transactions'] = $this->getTransactions($transactions, $scopedUserIds);

        // Family breakdown data
        $familyBreakdown = [];
        if ($user->isSharingBudget()) {
            foreach ($user->getSharedUsers() as $su) {
                $uBills = \App\Models\FinanceBill::where('user_id', $su->id)->with('currency')->get();
                $uTotalBalance = 0;
                foreach ($uBills as $b) {
                    $uTotalBalance += $b->calculateTotalBalance();
                }

                $uMonthIncome = (float)FinanceTransaction::where('user_id', $su->id)
                    ->where('is_balance', 1)
                    ->where('type', 'income')
                    ->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('currency_amount');

                $uMonthExpenses = abs((float)FinanceTransaction::where('user_id', $su->id)
                    ->where('is_balance', 1)
                    ->where('type', 'expenses')
                    ->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('currency_amount'));

                $familyBreakdown[] = [
                    'user' => $su,
                    'is_me' => ($su->id === $user->id),
                    'total_balance' => $uTotalBalance,
                    'month_income' => $uMonthIncome,
                    'month_expenses' => $uMonthExpenses,
                    'month_net' => $uMonthIncome - $uMonthExpenses,
                    'bills_count' => $uBills->count(),
                    'is_active' => ($filterUserId === $su->id),
                ];
            }
        }

        $data['family_breakdown'] = $familyBreakdown;
        $data['is_sharing_budget'] = $user->isSharingBudget();
        $data['filter_user_id'] = $filterUserId;
        $data['shared_users'] = $user->getSharedUsers();

        $taxService = new \App\Services\Finance\Fop\FopTaxService();
        $fop = $taxService->getFop($user->id);
        if ($fop) {
            $data['limitProgress'] = $taxService->getLimitProgress($fop, Carbon::now()->year);
        }

        return $data;
    }

    private function getTransactions($transactions, array $scopedUserIds)
    {
        return $transactions
            ->whereIn('user_id', $scopedUserIds)
            ->orderBy('id', 'DESC')
            ->take(10)
            ->get();
    }

    public function buttonChangeCurrency($code)
    {
        Auth::user()->setting()->update(["currency" => $code]);
        Currency::getExchangeRate($code);
        Toast::warning(__('Changed currency to ') . $code);
    }

    /*
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Information panel';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.finance.dashboard',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $userCurrency = Currency::getCurrencyCodeUser();
        $usdRate = Currency::getExchangeRate('USD');
        $eurRate = Currency::getExchangeRate('EUR');

        $usdText = 'USD' . ($usdRate && $usdRate != 1 ? ' (' . number_format($usdRate, 2) . ' ₴)' : '');
        $eurText = 'EUR' . ($eurRate && $eurRate != 1 ? ' (' . number_format($eurRate, 2) . ' ₴)' : '');

        $currentCurrencyText = $userCurrency;
        if ($userCurrency === 'USD')
            $currentCurrencyText = $usdText;
        if ($userCurrency === 'EUR')
            $currentCurrencyText = $eurText;

        $actions = [];

        if (Auth::user()?->isSharingBudget()) {
            $activeFilter = request()->get('user_id');
            $activeUser = $activeFilter ? \App\Models\User::find($activeFilter) : null;
            $filterLabel = $activeUser ? $activeUser->name : __('Всі (Спільний бюджет)');

            $userFilterLinks = [
                Link::make(__('Всі учасники (Спільний)'))
                    ->route('platform.main'),
            ];

            foreach (Auth::user()->getSharedUsers() as $su) {
                $suffix = $su->id === Auth::id() ? ' (' . __('Ви') . ')' : '';
                $userFilterLinks[] = Link::make($su->name . $suffix)
                    ->route('platform.main', ['user_id' => $su->id]);
            }

            $actions[] = DropDown::make(__('Учасник: ') . $filterLabel)
                ->icon('people')
                ->list($userFilterLinks);
        }

        $actions[] = DropDown::make(__("Currency: ") . $currentCurrencyText)
            ->list([
                Button::make('UAH')->method('buttonChangeCurrency', ['code' => 'UAH']),
                Button::make($usdText)->method('buttonChangeCurrency', ['code' => 'USD']),
                Button::make($eurText)->method('buttonChangeCurrency', ['code' => 'EUR']),
            ]);

        return $actions;
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $bills = $this->generateMetricsLayoutToBill(4, $this->filterUserId);
        return [
            Layout::view('dashboard.family-breakdown'),
            Layout::metrics(
                array_merge(['Total balance' => 'metrics.total.balance'], $bills)
            )->title('Bills'),

            Layout::metrics([
                'Income for this month' => 'metrics.currentMonth.income',
                'Expenses for this month' => 'metrics.currentMonth.expenses',
            ])->title('Data for the current month'),
            Layout::metrics([
                'Income for this year' => 'metrics.currentYear.income',
                'Expenses for this year' => 'metrics.currentYear.expenses',
            ])->title('Data for the current year'),
            Layout::view('fop.limit-progress-widget'),
            Layout::view('dashboard.category-list'),
            DashboardChartIncomeLayout::make('charts.income_year', __('Income for the year')),
            DashboardChartCapitalLayout::make('charts.capital', __('Capital Statistics')),
            DashboardChartTransactionLayout::make('charts.transactions', __('Statistics for the year')),
            TransactionListLayout::class,
        ];
    }


}
