<?php

namespace App\Orchid\Screens;

use App\Models\FinanceCurrency;
use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Dashboard\DashboardChartLayout;
use App\Orchid\Layouts\Examples\ChartBarExample;
use App\Orchid\Layouts\Examples\ChartLineExample;
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
        $currency = FinanceCurrency::find(1);

        $balance = $transactions->sum('amount');
        $income = $transactions->where('finance_transaction_type_id',1) ;
        $expense = $transactions->where('finance_transaction_type_id',2) ;

        return [
            'metrics' => [
                'balance'   => ['value' => number_format($balance) . " " . $currency->code ],
                'income' => ['value' => number_format($income->sum('amount') ) . " " . $currency->code],
                'expense'   => ['value' => number_format( $expense->sum('amount')  ) . " " . $currency->code],
            ],
            'charts'  => [
                $transactions->sumByDays('amount')->toChart(__('Amount')),
            ],
            'table' => $transactions->orderBy('id' , 'DESC')->take(10)->get()

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
    {
        return [

            Layout::metrics([
                'Total balance'    => 'metrics.balance',
                'Income' => 'metrics.income',
                'Expense' => 'metrics.expense',
            ]),
            Layout::columns([
                DashboardChartLayout::make('charts', 'Total balance')

            ]),
            Layout::table('table', [
                TD::make('finance_transaction_category_id', __('Category'))
                    ->render(
                        fn(FinanceTransaction $transaction) => $transaction->transactionCategory->name
                    ),
                TD::make('finance_transaction_type_id', __('Type'))
                    ->render(
                        fn(FinanceTransaction $transaction) => $transaction->transactionType->name
                    ),
                TD::make('amount', __('Amount'))
                    ->render(function ($transaction){
                        return view('finance.transaction.partials.amount', ['amount' => $transaction->amount, 'currency' => $transaction->currency->code ] );
                    }),

                TD::make('created_at', __('Created'))
                    ->usingComponent(DateTimeSplit::class)


            ])->title(__('History transactions')),


        ];
    }
}
