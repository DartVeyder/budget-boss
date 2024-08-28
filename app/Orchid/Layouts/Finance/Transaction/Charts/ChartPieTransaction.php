<?php

namespace App\Orchid\Layouts\Finance\Transaction\Charts;

use Orchid\Screen\Layouts\Chart;

class ChartPieTransaction extends Chart
{
    /**
     * Add a title to the Chart.
     *
     * @var string
     */
    protected $title = 'DemoCharts';
    protected $maxSlices = 6;

    /**
     * Available options:
     * 'bar', 'line',
     * 'pie', 'percentage'
     *
     * @var string
     */
    protected $type = 'pie';

    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the charts.
     *
     * @var string
     */
    protected $target = 'charts';
    protected $height = '500';

}
