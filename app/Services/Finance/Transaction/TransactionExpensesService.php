<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransactionMcc;
use App\Models\FinanceTransactionSource;
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
        $transaction['balance'] = $this->getTotalBalance() -  $transaction['amount'];
        $transaction['balance_bill'] = $this->getBalanceToBill($transaction['finance_bill_id']) -  $transaction['amount'];
        $transaction['amount'] = $this->getAmountNegative($transaction['amount']);
        $transaction['user_id'] = $this->getUserId() ;

        $transaction = array_merge($transaction, $this->getCurrency($transaction['finance_bill_id'], $transaction['amount']));

        return   $transaction;
    }

    public function createInsertDataMono($data):array
    {
        $transactions  = [];
        $data = array_reverse($data);
        $balance_bill =  $this->getBalanceToBill(3) ;
        $balance = $this->getTotalBalance();
        foreach ( $data as $item){
            if($item['amount'] > 0){
                continue;
            }
            $source_name = str_replace("\n", " ",mb_strtolower ($item['description']));
            $transaction_source = FinanceTransactionSource::firstOrCreate(['name' => $source_name], ['name' => $source_name]);

            $date = $this->getDate($item['time']);
            $balance -=   abs($this->getAmount($item['amount']));
            $balance_bill  -= abs($this->getAmount($item['amount']));
            $transaction = [
                'created_at' =>  $date,
                'updated_at' =>  $date,
                'accrual_date' =>   $date,
                'transaction_category_id' => $this->getCategoryWithMcc($item['mcc']),
                'finance_bill_id' => 3,
                'source_name' =>$source_name ,
                'transaction_source_id' => $transaction_source->id,
                'mcc_code'=>$item['mcc'],
                'amount' => $this->getAmount($item['amount']),
                'mono_id' => $item['id'],
                'balance' => $balance,
                'balance_bill' =>  $balance_bill,
                'transaction_type_id' => 1,
                'type' => 'expenses',
                'user_id' => $this->getUserId()

            ];

            FinanceTransactionMcc::firstOrCreate(['code' => $item['mcc']], ['code' => $item['mcc']]);
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
        if(is_null($mcc)){
            return null;
        }
        return  $mcc->categories->first()->id  ?? null;
    }

}
