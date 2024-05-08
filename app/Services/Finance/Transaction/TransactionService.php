<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Support\Facades\Toast;

class TransactionService
{
    public function save(FinanceTransaction $transaction, Request $request): void{
        $data = $request->input('transaction');
        $transactionsCurrency = FinanceTransaction::where('finance_currency_id' , $data['finance_currency_id']);

        $amount = $this->getAmount($data['finance_transaction_type_id'], $data['amount']);
        $data['user_id'] = Auth::user()->id;
        $data['amount'] = $amount;
        $data['balance'] = $this->calculateBalance($amount,  $transactionsCurrency->sum('amount'));

        $transaction->fill($data)->save();
    }

    private function getAmount(int $type, float $amount): float{
         return ($type == 2) ? -1 * abs($amount): $amount;
    }

    private function calculateBalance(float $amount, float $beforeBalance): float{
        return  $beforeBalance + $amount;
    }
}
