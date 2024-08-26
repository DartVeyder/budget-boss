<?php

namespace App\Orchid\Screens;

use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Dashboard\DashboardChartTransactionCategoryLayout;
use App\Orchid\Layouts\Dashboard\DashboardChartTransactionLayout;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
use App\Services\Currency\Currency;
use App\Services\Finance\Bill\BillService;
use App\Services\Finance\Crypto\Binance\BinanceService;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use App\Services\Finance\Transaction\TransactionsService;
use App\Services\Metrics\Chartable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DashboardScreen extends Screen
{
    use BillService;
    use Chartable;

    private int $userId;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransaction $transactions ): iterable
    {
        Currency::updateExchangeRates(["USD","EUR"]);
        $start =   Carbon::now()->subYear();;
        $end =    Carbon::now();

        $user = Auth::user();
        $this->userId = $user->id;
        $currentMonth = Carbon::now()->month;

        $data = [];


        $income = $transactions->where('is_balance' ,1)->where('type','income')->where('user_id', $user->id) ;
        $expenses = $transactions->where('is_balance' ,1)->where('type','expenses')->where('user_id', $user->id) ;

        $transaction = new TransactionsService();
        $transactionIncome = new TransactionIncomeService($user->id);
        $transactionExpenses = new TransactionExpensesService($user->id);

        $data['metrics']['total']['balance']  =  Currency::convertValueToCurrency($transaction->getTotalBalance());
        $data['metrics']['currentMonth']['income'] =  Currency::convertValueToCurrency($income->whereMonth('created_at', $currentMonth )->whereYear('finance_transactions.created_at', Carbon::now()->year)->sum('currency_amount'));
        $data['metrics']['currentMonth']['expenses'] =  Currency::convertValueToCurrency($expenses->whereMonth('created_at', $currentMonth )->whereYear('finance_transactions.created_at', Carbon::now()->year)->sum('currency_amount'));
        $data['metrics']['bills'] =  $this->generateMetricsToBill();
        $data['charts']['transactions'][] = $transactionIncome->chartBar(__("Income"),$start, $end,'accrual_date','currency_amount');
        $data['charts']['transactions'][] = $transactionExpenses->chartBar(__("Expenses"),$start, $end,'accrual_date','absolute_currency_amount');
        $data['charts']['transactions'][] = $transaction->chartBarBalance(__("Balance"),$start, $end,'accrual_date','currency_amount');
        $data['charts']['categories']['income'] = $transactionIncome->chartPieCategory(Carbon::now()->startOfMonth(), $end);
        $data['charts']['categories']['expenses'] = $transactionExpenses->chartPieCategory(Carbon::now()->startOfMonth(), $end);

        $data['transactions'] = $this-> getTransactions($transactions);

        return  $data;
    }



    private  function  getTransactions($transactions){
       return $transactions
            ->where('user_id', $this->userId)
            ->orderBy('id' , 'DESC')
            ->take(10)
            ->get();
    }


    public function buttonChangeCurrency($code){
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

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            DropDown::make(__("Currency: ") . Currency::getCurrencyCodeUser())
                ->list(
                    [
                    Button::make('UAH')->method('buttonChangeCurrency',['code' => 'UAH']),
                    Button::make('USD')->method('buttonChangeCurrency',['code' => 'USD']),
                    Button::make('EUR')->method('buttonChangeCurrency',['code' => 'EUR']),
                ]),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $bills = $this->generateMetricsLayoutToBill(4);
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
            TransactionListLayout::class,
        ];
    }


}
