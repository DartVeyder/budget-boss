<?php

namespace App\Services\Metrics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SaKanjo\EasyMetrics\Metrics\Trend;
trait Chartable
{
    private array $chart;

    public function toCharts($model, $name = ""): array {

        $data = Trend::make( $model)
            ->ranges([ 12 ])
            ->dateColumn('accrual_date')
            ->sumByMonths('currency_amount');

        return [
            'name' => $name,
            'labels' => $data->getLabels(),
            'values' =>  $data->getData()  ,
        ];
    }

    public  function toChart( $name = "")
    {

        $data =    [
            'name' => $name,
            'labels' => array_column( $this->chart, 'label'),
            'values' =>  array_map('abs',array_column( $this->chart, 'value')),
        ];
        dd( $data);
    }

    public function sumByMonth( $query, $dateColumn, $dataColumn){

        $this->chart = $query
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as label"), DB::raw('SUM(currency_amount) as value'))
            ->groupBy('label')
            ->orderBy('label', 'asc')
            ->get()
            ->toArray();
       return $this;
    }

}
