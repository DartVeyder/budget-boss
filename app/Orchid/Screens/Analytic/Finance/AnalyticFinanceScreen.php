<?php

namespace App\Orchid\Screens\Analytic\Finance;

use App\Orchid\Layouts\Analytic\Finance\AnalyticFinanceSelection;
use App\Orchid\Layouts\Finance\Transaction\Charts\ChartBarTransaction;
use App\Orchid\Layouts\Finance\Transaction\Charts\ChartPieTransaction;
use App\Orchid\Layouts\Finance\Transaction\Charts\ChartPieTransactionLayout;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
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
        $start = ($request->get('created_at')['start']) ?? Carbon::now()->subYear();;
        $end = ($request->get('created_at')['end']) ?? Carbon::now();

        $transaction = new TransactionsService();
        $transactionIncome = new TransactionIncomeService();
        $transactionExpenses = new TransactionExpensesService();

        $data['charts']['transactions'][] = $transactionIncome->chartBar(__("Income"),$start, $end,'accrual_date','currency_amount');
        $data['charts']['transactions'][] = $transactionExpenses->chartBar(__("Expenses"),$start, $end,'accrual_date','absolute_currency_amount');
        $data['charts']['transactions'][] = $transaction->chartBarBalance(__("Balance"),$start, $end,'accrual_date','currency_amount');
        $data['metrics']['sum']['income'] = $transactionIncome->getSum('currency_amount',true, AnalyticFinanceSelection::class,  $start ,$end );
        $data['metrics']['sum']['expenses'] = $transactionExpenses->getSum('currency_amount',true, AnalyticFinanceSelection::class, $start ,$end );
        $data['metrics']['sum']['total'] = $transaction->getTotalAmount('currency_amount',true, AnalyticFinanceSelection::class, $start ,$end );
        $data['charts']['categories']['income'] = $transactionIncome->chartPieCategory($start, $end, __('Income'));
        $data['charts']['categories']['expenses'] = $transactionExpenses->chartPieCategory($start, $end);
        $data['charts']['income']['bill'] = $transactionIncome->chartPieBill($start, $end);
        $data['charts']['income']['customer'] = $transactionIncome->chartPieCustomer($start, $end);
        $data['charts']['expenses']['bill'] = $transactionExpenses->chartPieBill($start, $end);
        $data['transactions'] =   $transaction->list(AnalyticFinanceSelection::class);

        return $data ;
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
            AnalyticFinanceSelection::class,
            ChartBarTransaction::make('charts.transactions'),
            Layout::metrics([
                'Balance' => 'metrics.sum.total',
                'Income' => 'metrics.sum.income',
                'Expenses' => 'metrics.sum.expenses',
            ]),
            Layout::accordion([
                'Категорії' => Layout::columns([
                    ChartPieTransaction::make('charts.categories.income', __('Income')),
                    ChartPieTransaction::make('charts.categories.expenses', __('Expenses')),
                ])]),
            Layout::accordion([
                'Доходи' => Layout::columns([
                    ChartPieTransaction::make('charts.income.bill', __('По рахунках')),
                    ChartPieTransaction::make('charts.income.customer', __('По замовниках')),
                ])
            ]),
            Layout::accordion([
                'Витрати' => Layout::columns([
                    ChartPieTransaction::make('charts.expenses.bill', __('По рахунках')),
                ])
            ]),
            Layout::accordion([
                'Транзакції' =>  Layout::columns([
                     TransactionListLayout::class,
                ])
            ])
        ];
    }
}
