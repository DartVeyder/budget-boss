<?php

namespace App\Orchid\Screens;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Orchid\Layouts\Dashboard\DashboardChartTransactionCategoryLayout;
use App\Orchid\Layouts\Dashboard\DashboardChartTransactionLayout;
use App\Orchid\Layouts\Examples\ChartBarExample;
use App\Orchid\Layouts\Examples\ChartLineExample;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
use App\Orchid\Screens\Components\Cells\DateTime;
use App\Services\Finance\Bill\BillService;
use App\Services\Metrics\Chartable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Orchid\Screen\Actions\Link;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use SaKanjo\EasyMetrics\Metrics\Trend;
use SaKanjo\EasyMetrics\Metrics\Value;

class DashboardScreen extends Screen
{
    use BillService;
    use Chartable;



    private function toChart(){

    }
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransaction $transactions ): iterable
    {
        $currentMonth = Carbon::now()->month;
        $user_id = Auth::user()->id;
        $income = $transactions->where('transaction_type_id',2)->where('user_id', $user_id) ;
        $expenses = $transactions->where('transaction_type_id',1)->where('user_id', $user_id) ;

        $balance =   $income->sum('amount') - $expenses->sum('amount');

        $chart_income =  $this->toCharts($income, __('Income'));
        $chart_expense =  $this->toCharts($expenses, __('Expenses'));


        return [
            'metrics' => [
                "total" => [
                    'balance'   => ['value' => number_format($balance , 0,'.',' ' ) . " ₴" ],
                ],
                "currentMonth"=>[
                    'income' => ['value' => number_format( $income->whereMonth('created_at', $currentMonth )->sum('amount')  , 0,'.',' ' ). " ₴" ],
                    'expenses'   => ['value' => number_format( $expenses->whereMonth('created_at', $currentMonth )->sum('amount')  , 0,'.',' '  ) . " ₴" ],
                ],
                "bills" =>  $this->generateMetricsToBill()
            ],
            'charts'  => [
                'transactions' =>[
                    $chart_income,
                    $chart_expense
                ],
                'categories' =>[
                    'expenses' => $this->getCategoriesChart(1),
                    'income' => $this->getCategoriesChart(2)
                ]
            ],
            'transactions' => $transactions
                ->where('transaction_type_id', "!=" , 3)
                ->where('user_id', $user_id)
                ->orderBy('id' , 'DESC')
                ->take(10)
                ->get()

        ];
    }

    private function getCategoriesChart(int $type): array{
        $collection = DB::table('finance_transactions')
            ->whereMonth('finance_transactions.created_at', Carbon::now()->month)
            ->where('finance_transactions.user_id', Auth::user()->id)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.transaction_type_id', $type)
            ->join('finance_transaction_categories', 'finance_transactions.transaction_category_id', '=', 'finance_transaction_categories.id')
            ->select('finance_transaction_categories.id', 'finance_transaction_categories.name', DB::raw('SUM(finance_transactions.amount) as total_amount'))
            ->groupBy('finance_transaction_categories.id', 'finance_transaction_categories.name')
            ->get();

        return [[
            'labels' => $collection->pluck('name')->toArray(),
            'values' => $collection->pluck('total_amount')->toArray()
        ]];
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
        $bills = $this->generateMetricsLayoutToBill();
        return [
            Layout::metrics(
                array_merge([ 'Total balance'    => 'metrics.total.balance'] ,$bills  )
            )->title('Bills'),
            Layout::metrics([
                'Income for this month' => 'metrics.currentMonth.income',
                'Expenses for this month' => 'metrics.currentMonth.expenses',
            ])->title( 'Data for the current month'),


            Layout::columns([
                DashboardChartTransactionCategoryLayout::make('charts.categories.income'),
                DashboardChartTransactionCategoryLayout::make('charts.categories.expenses'),
            ]),
            DashboardChartTransactionLayout::make('charts.transactions', __('Statistics for the year')),
            TransactionListLayout::class


        ];
    }


}
