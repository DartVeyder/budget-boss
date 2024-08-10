<?php

namespace App\Orchid\Screens\Analytic\Finance;

use App\Orchid\Layouts\Analytic\Finance\AnalyticFinanceSelection;
use App\Orchid\Layouts\Finance\Transaction\Charts\ChartBarTransaction;
use App\Orchid\Layouts\Finance\Transaction\TransactionSelection;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Screen;

class AnalyticFinanceScreen extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        $start = ($request->get('created_at')['start']) ?? null;
        $end = ($request->get('created_at')['end']) ?? null;
        $userId = Auth::user()->id;
        $transactionIncome = new TransactionIncomeService($userId);
        $transactionExpenses = new TransactionExpensesService($userId);

        $data['charts']['transactions'][] = $transactionIncome->query()->SumByMonths('currency_amount',  $start, $end, 'accrual_date')->toChart(__("Income"));
        $data['charts']['transactions'][] = $transactionExpenses->query()->SumByMonths('currency_amount',  $start, $end, 'accrual_date')->toChart(__("Expenses"));


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
        ];
    }
}
