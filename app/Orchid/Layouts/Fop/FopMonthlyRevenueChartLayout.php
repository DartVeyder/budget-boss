<?php

namespace App\Orchid\Layouts\Fop;

use Orchid\Screen\Layouts\Chart;

class FopMonthlyRevenueChartLayout extends Chart
{
    /**
     * Add a title to the Chart.
     *
     * @var string
     */
    protected $title = 'Динаміка доходів за місяцями (порівняння з минулим роком)';

    /**
     * Available options:
     * 'bar', 'line', 'pie', 'percentage'
     *
     * @var string
     */
    protected $type = 'bar';

    /**
     * Data source key.
     *
     * @var string
     */
    protected $target = 'monthlyRevenueChart';

    /**
     * Colors used in chart.
     *
     * @var array
     */
    protected $colors = [
        '#4f46e5', // Indigo for current year
        '#94a3b8', // Slate for previous year
    ];

    /**
     * Height of chart.
     *
     * @var int
     */
    protected $height = 300;
}
