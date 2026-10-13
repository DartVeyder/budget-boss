<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Orchid\Filters\Finance\Transaction\CreatedFilter;
use App\Orchid\Filters\Finance\Transaction\MonthTransactionFilter;
use App\Orchid\Filters\Finance\Transaction\TypeTransactionFilter;
use App\Orchid\Filters\Finance\Transaction\UserTransactionFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class TransactionSelection extends Selection
{
    /**
     * @return Filter[]
     */
    public function filters(): iterable
    {
        return [
            UserTransactionFilter::class,
            TypeTransactionFilter::class,
            MonthTransactionFilter::class,
            CreatedFilter::class,
        ];
    }
}
