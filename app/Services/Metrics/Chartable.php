<?php

namespace App\Services\Metrics;

use SaKanjo\EasyMetrics\Metrics\Trend;

trait Chartable
{

    public function toCharts($model, $name = ""): array {

        $data = Trend::make( $model)
            ->ranges([ 12 ])
            ->dateColumn('accrual_date')
            ->sumByMonths('currency_amount');

        return [
            'name' => $name,
            'labels' => $data->getLabels(),
            'values' =>  array_map('abs',$data->getData()) ,
        ];
    }
}
