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
use App\Services\Currency\Currency;
use App\Services\Finance\Bill\BillService;
use App\Services\Metrics\Chartable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use SaKanjo\EasyMetrics\Metrics\Trend;
use SaKanjo\EasyMetrics\Metrics\Value;

class DashboardScreen extends Screen
{
    use BillService;
    use Chartable;

    private string $currency = 'UAH';
    private string $currencySymbol;
    private float $exchangeRate = 1;
    private int $userId;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransaction $transactions ): iterable
    {
        $user = Auth::user();
        $this->userId = $user->id;
        $this->currency = $user->setting->currency;

        $this->exchangeRate = Currency::getExchangeRate(  $this->currency ) ;
        $this->currencySymbol = Currency::getSymbol(  $this->currency );
        $currentMonth = Carbon::now()->month;

        $data = [];

        $data['metrics']['total']['balance']  =  $this->getFormatMoney($this->calculateAmountToCurrency($user->transactions()->sum('currency_amount')) );
        $income = $transactions->where('type','income')->where('user_id', $user->id) ;
        $expenses = $transactions->where('type','expenses')->where('user_id', $user->id) ;


        $data['metrics']['currentMonth']['income'] =  $this->getFormatMoney($this->calculateAmountToCurrency($income->whereMonth('created_at', $currentMonth )->sum('currency_amount')));
        $data['metrics']['currentMonth']['expenses'] =  $this->getFormatMoney($this->calculateAmountToCurrency($expenses->whereMonth('created_at', $currentMonth )->sum('currency_amount')));
        $data['metrics']['bills'] =  $this->generateMetricsToBill();
        $data['charts']['transactions'][] = $this->toCharts($income, __('Income'));
        $data['charts']['transactions'][] =$this->toCharts($expenses, __('Expenses'));
        $data['charts']['categories']['expenses'] = $this->getCategoriesChart('expenses');
        $data['charts']['categories']['income'] = $this->getCategoriesChart('income');
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

    private  function getFormatMoney(float $value): string{
        return number_format($value , 0,'.',' ' ) . " " . $this->currencySymbol;
    }

    private function calculateAmountToCurrency(float $amount):float{
        return  $amount / $this->exchangeRate  ;
    }

    private function getCategoriesChart(string $type): array{
        $collection = DB::table('finance_transactions')
            ->whereMonth('finance_transactions.created_at', Carbon::now()->month)
            ->where('finance_transactions.user_id', Auth::user()->id)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $type)
            ->join('finance_transaction_categories', 'finance_transactions.transaction_category_id', '=', 'finance_transaction_categories.id')
            ->select('finance_transaction_categories.id', 'finance_transaction_categories.name', DB::raw('ABS(SUM(finance_transactions.amount * finance_transactions.currency_value)) as total_amount'))
            ->groupBy('finance_transaction_categories.id', 'finance_transaction_categories.name')
            ->get();
        return [[
            'labels' => $collection->pluck('name')->toArray(),
            'values' => $collection->pluck('total_amount')->toArray()
        ]];
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
            DropDown::make(__("Currency: ") . $this->currency)
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
            TransactionListLayout::class,
        ];
    }


}
