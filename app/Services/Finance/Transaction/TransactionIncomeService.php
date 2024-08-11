<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransaction;

class TransactionIncomeService extends  TransactionsService
{
    protected string $type = 'income';

    public function __construct() {
        $this->setType($this->type);
        parent::__construct();
    }

}
