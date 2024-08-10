<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransaction;

class TransactionExpensesService
{
    private int $userId;
    public function __construct(int $userId)
    {

         $this->setUserId($userId);
    }
    public function query( )
    {
        return FinanceTransaction::where('type','expenses')->where('user_id', $this->getUserId()) ;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
}
