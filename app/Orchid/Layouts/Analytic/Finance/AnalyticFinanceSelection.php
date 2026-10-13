<?php

namespace App\Orchid\Layouts\Analytic\Finance;

use App\Orchid\Filters\Analytic\Finance\BillFilter;
use App\Orchid\Filters\Analytic\Finance\CategoryFilter;
use App\Orchid\Filters\Analytic\Finance\CreatedFilter;
use App\Orchid\Filters\Analytic\Finance\UserFilter;
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
            CreatedFilter::class,
            UserFilter::class,
            BillFilter::class,
            CategoryFilter::class,
        ];
    }
}
