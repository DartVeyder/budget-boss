<?php

namespace App\Orchid\Screens\Fop;

use App\Models\Fop;
use App\Orchid\Layouts\Fop\FopMonthlyRevenueChartLayout;
use App\Services\Finance\Fop\FopAnalyticService;
use Carbon\Carbon;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class FopAnalyticScreen extends Screen
{
    /**
     * @var Fop|null
     */
    public $fop;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $analyticService = new FopAnalyticService();
        $fop = $analyticService->getFop();

        if (!$fop) {
            return [
                'fop' => null,
            ];
        }

        $this->fop = $fop;
        $year = (int)request()->get('year', Carbon::now()->year);
        $quarter = request()->has('quarter') && request()->get('quarter') !== ''
            ? (int)request()->get('quarter')
            : null;

        $kpi = $analyticService->getKpiSummary($fop, $year, $quarter);
        $monthlyRevenueChart = $analyticService->getMonthlyRevenueChart($fop, $year);
        $quarterly = $analyticService->getQuarterlyBreakdown($fop, $year);
        $customers = $analyticService->getCustomerAnalytics($fop, $year, $quarter);
        $counterparties = $analyticService->getCounterpartyAnalytics($fop, $year, $quarter);
        $compliance = $analyticService->getDocumentCompliance($fop, $year);
        $recentTransactions = $analyticService->getRecentFopTransactions($fop, 8);
        $availableYears = $analyticService->getAvailableYears($fop);

        return [
            'fop' => $fop,
            'selectedYear' => $year,
            'selectedQuarter' => $quarter,
            'availableYears' => $availableYears,
            'kpi' => $kpi,
            'monthlyRevenueChart' => $monthlyRevenueChart,
            'quarterly' => $quarterly,
            'customers' => $customers,
            'counterparties' => $counterparties,
            'compliance' => $compliance,
            'recentTransactions' => $recentTransactions,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Аналітика ФОП';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Глибока аналітика доходів, податкового навантаження, структури клієнтів та стану документообігу ФОП.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Профіль ФОП')
                ->icon('bs.person-gear')
                ->route('platform.fops'),

            Link::make('Податки та Календар')
                ->icon('bs.calculator')
                ->route('platform.fop.tax'),

            Link::make('Книга доходів')
                ->icon('bs.book')
                ->route('platform.fop.ledger'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        if (!$this->fop) {
            return [
                Layout::view('platform::partials.welcome', [
                    'title' => 'ФОП ще не створено',
                    'text' => 'Будь ласка, спочатку заповніть інформацію про свій ФОП у розділі "Мій ФОП".'
                ])
            ];
        }

        return [
            Layout::view('fop.analytic.header-filters'),
            Layout::view('fop.analytic.metrics'),
            FopMonthlyRevenueChartLayout::class,
            Layout::view('fop.analytic.quarterly-table'),
            Layout::view('fop.analytic.customers-table'),
            Layout::view('fop.analytic.compliance-and-recent'),
        ];
    }
}
