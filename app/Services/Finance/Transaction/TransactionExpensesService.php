<?php

namespace App\Services\Finance\Transaction;

use Illuminate\Http\Request;

class TransactionExpensesService extends  TransactionsService
{
    protected string $type = 'expenses';
    public function __construct() {
        $this->setType($this->type);
        parent::__construct();
    }

    public function createInsertData(Request $request): array{
        $transaction = $request->input('transaction');
        if(!$transaction['created_at']){
            unset($transaction['created_at']);
        }

        $transaction['amount'] = $this->getAmountNegative($transaction['amount']);
        $transaction['user_id'] = $this->getUserId();
        $transaction = array_merge($transaction, $this->getCurrency($transaction['finance_bill_id'], $transaction['amount']));
        return   $transaction;
    }


}
