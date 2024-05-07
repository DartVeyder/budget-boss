<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    public function save(FinanceTransactions $transactions, Request $request): void{
        $data = $request->all();
        $transactionsCurrency = FinanceTransactions::where('finance_currency_id' , $data['finance_currency_id']);

        $amount = $this->getAmount($data['finance_transaction_type_id'], $data['amount']);
        $data['user_id'] = Auth::user()->id;
        $data['amount'] = $amount;
        $data['balance'] = $this->calculateBalance($amount,  $transactionsCurrency->sum('amount'));

        $transactions->fill($data)->save();
    }

    private function getAmount(int $type, float $amount): float{
         return ($type == 2) ? -1 * abs($amount): $amount;
    }

    private function calculateBalance(float $amount, float $beforeBalance): float{
        return  $beforeBalance + $amount;
    }
}
