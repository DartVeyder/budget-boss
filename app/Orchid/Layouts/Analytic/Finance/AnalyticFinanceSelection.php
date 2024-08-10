<?php

namespace App\Orchid\Layouts\Analytic\Finance;

use App\Orchid\Filters\Analytic\Finance\CreatedFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class AnalyticFinanceSelection extends Selection
{
    /**
     * @return Filter[]
     */
    public function filters(): iterable
    {
        return [
            CreatedFilter::class
        ];
    }
}
