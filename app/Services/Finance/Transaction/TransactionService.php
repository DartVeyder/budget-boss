<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransactions;
use Illuminate\Http\Request;

class TransactionService
{
    public function save(FinanceTransactions $transactions, Request $request){
        dd($request->all());
    }
}
