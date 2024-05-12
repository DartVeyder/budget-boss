<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Orchid\Filters\Finance\Transaction\TypeTransactionFilter;
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
            TypeTransactionFilter::class
        ];
    }
}
