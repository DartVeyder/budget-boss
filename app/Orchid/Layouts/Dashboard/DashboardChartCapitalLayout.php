<?php

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Screen\Layouts\Chart;

class DashboardChartCapitalLayout extends Chart
{
    /**
     * Height of the chart.
     *
     * @var int
     */
    protected $height = 300;

    /**
     * Available options:
     * 'bar', 'line',
     * 'pie', 'percentage'
     *
     * @var string
     */
    protected $type = 'line';

    /**
     * Configuring line.
     *
     * @var array
     */
    protected $lineOptions = [
        'spline'     => 1,
        'regionFill' => 1,
        'hideDots'   => 0,
        'hideLine'   => 0,
        'heatline'   => 0,
        'dotSize'    => 4,
    ];

    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the charts.
     *
     * @var string
     */
    protected $target = 'charts.capital';

    /**
     * Colors used.
     *
     * @var array
     */
    protected $colors = [
        '#6366f1', // Premium indigo color for capital
    ];
}
