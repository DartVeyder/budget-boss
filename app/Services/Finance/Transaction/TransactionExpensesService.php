<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransaction;

class TransactionExpensesService extends  TransactionsService
{
    protected string $type = 'expenses';
    public function __construct() {
        $this->setType($this->type);
        parent::__construct();
    }
}
