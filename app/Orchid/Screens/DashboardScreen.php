<?php

namespace App\Orchid\Screens;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Dashboard\DashboardChartLayout;
use App\Orchid\Layouts\Examples\ChartBarExample;
use App\Orchid\Layouts\Examples\ChartLineExample;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class DashboardScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransaction $transactions ): iterable
    {
        $bills = $this->generateMetricsToBill();
        $bills11 = $this->generateMetricsLayoutToBill();

        $aa = [
            'Total balance'    => 'metrics.total.balance',
            'Income' => 'metrics.total.income',
            'Expense' => 'metrics.total.expense',
        ];
        dd($bills,$bills11, $aa);
        $income = $transactions->where('type','income') ;
        $expenses = $transactions->where('type','expenses') ;
        $balance =   $income->sum('amount') - $expenses->sum('amount');
        return [
            'metrics' => [
                "total" => [
                    'balance'   => ['value' => number_format($balance)  ],
                    'income' => ['value' => number_format($income->sum('amount') ) ],
                    'expense'   => ['value' => number_format( $expenses->sum('amount')  )  ],
                ],
                "bills" =>  $bills

            ],
            'charts'  => [
                $income->sumByDays('amount')->toChart(__('Income')),
                $expenses->sumByDays('amount')->toChart(__('Expenses')),
            ],
            'table' => $transactions
                ->where('transaction_type_id', "!=" , 3)
                ->where('user_id' , Auth::user()->id)
                ->orderBy('id' , 'DESC')
                ->take(10)
                ->get()

        ];
    }

    /*E
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
    {  $template = Layout::view('platform::dummy.block');

        return [
            Layout::metrics(
                $this->generateMetricsLayoutToBill()
            ),
            Layout::metrics([
                'Total balance'    => 'metrics.total.balance',
                'Income' => 'metrics.total.income',
                'Expense' => 'metrics.total.expense',
            ]),

           // DashboardChartLayout::make('charts', 'Total balance'),

            Layout::table('table', [
                TD::make('transaction_category_id', __('Category'))
                    ->render(
                        fn(FinanceTransaction $transaction) => ($transaction->category)? $transaction->category->name : ''
                    ),
                TD::make('Bill', __('Bill'))
                    ->render(
                        fn(FinanceTransaction $transaction) => $transaction->bill->name
                    ),
                TD::make('amount', __('Amount'))
                    ->render(function ($transaction){
                        return view('finance.transaction.partials.amount', $transaction );
                    }),

                TD::make('created_at', __('Created'))
                    ->usingComponent(DateTimeSplit::class)
                    ->align(TD::ALIGN_RIGHT)
                    ->sort(),


            ])->title(__('History transactions')),


        ];
    }

    private function generateMetricsToBill(){
        $data = [];
        $bills = FinanceBill::all();
        foreach ($bills as $bill){
            $data[ Hash::make( $bill->name)] =
                [
                   'income' => $bill->transactions->where('type','income')->sum('amount'),
                    'expenses' =>   $bill->transactions->where('type','expenses')->sum('amount')
                ];
        }
        return $data;
    }

    private function generateMetricsLayoutToBill(){
        $data = [];
        $bills = FinanceBill::all();
        foreach ($bills as $bill){
            $data[$bill->name] =  "metrics.bills.". Hash::make( $bill->name).".income";
        }
        return $data ;
    }

}
