<?php

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Screen\Layouts\Chart;

class DashboardChartIncomeLayout extends Chart
{
    /**
     * Add a title to the Chart.
     *
     * @var string
     */
    protected $title = 'Income';

    /**
     * Available options:
     * 'bar', 'line',
     * 'pie', 'percentage'
     *
     * @var string
     */
    protected $type = 'line';

    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the charts.
     *
     * @var string
     */
    protected $target = 'charts.income_year';

    /**
     * Colors used.
     *
     * @var array
     */
    protected $colors = [
        '#28a745', // Green for current year
        '#adb5bd', // Gray for previous year
    ];
}
