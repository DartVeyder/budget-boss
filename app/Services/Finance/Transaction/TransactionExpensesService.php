<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransactionMcc;
use Carbon\Carbon;
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

    public function createInsertDataMono($data):array
    {
        $transactions  = [];
        $data = array_reverse($data);
        foreach ( $data as $item){
            if($item['amount'] > 0){
                continue;
            }


            $date = $this->getDate($item['time']);

            $transaction = [
                'created_at' =>  $date,
                'accrual_date' =>   $date,
                'transaction_category_id' => $this->getCategoryWithMcc($item['mcc']),
                'finance_bill_id' => 8,
                'source_name' => mb_strtolower ($item['description']),
                'amount' => $this->getAmount($item['amount']),
                'mono_id' => $item['id'],
                'transaction_type_id' => 1,
                'type' => 'expenses',
                'user_id' => $this->getUserId()

            ];

            $transaction = array_merge($transaction, $this->getCurrency($transaction['finance_bill_id'], $transaction['amount']));
            $transactions[] = $transaction;

        }

        return  $transactions;
    }

    private function getDate($time):string
    {
        $carbonDate = Carbon::createFromTimestamp($time);
        return $carbonDate->format('Y-m-d H:i:s');

    }

    private function getAmount(int $amount):float
    {
        return $amount / 100;
    }

    private function getCategoryWithMcc(int $code):int|null
    {
        $mcc = FinanceTransactionMcc::where('code', $code)->first();
        return ($mcc)? $mcc->categories->first()->id : null;
    }

}
