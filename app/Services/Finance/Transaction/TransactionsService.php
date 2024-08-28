<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Services\Currency\Currency;
use App\Services\Finance\Crypto\Binance\BinanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionsService
{
    protected int $userId;
    protected string $type ;
    public function __construct()
    {
        $this->setUserId(Auth::user()->id);
    }

    public  function  list($filter = null, $start = null,$end = null){
        $query = FinanceTransaction::where('user_id', $this->getUserId()) ;;
        if($filter){
            $query  =   $query ->filters( $filter);
        }else{
            $query =  $query ->whereBetween('created_at', [$start, $end]);
        }
             return $query->orderBy('created_at' , 'DESC')->paginate();
    }

    public function getTotalAmount(  string $value, bool $isCurrency = false, $filter = null, $start = null,$end = null ):float|string
    {
        $query = FinanceTransaction::where('user_id', $this->getUserId())->where('is_balance' ,1) ;
        if($filter &&  isset($_GET['created_at'])  ){
            $query =  $query->filters( $filter);
        }else{
            $query = $query->whereBetween('created_at', [$start, $end]);

        }

        if($isCurrency){
            return  Currency::convertValueToCurrency($query->sum($value));
        }

        return $query;

    }
    public function getTotalBalance():float{
        return (float)$this->getTotalAmountInUsd() + BinanceService::getBalanceUAH();
    }
    public function getTotalAmountInUsd() :float{
        return (float) FinanceTransaction::leftJoin('finance_currencies', 'finance_transactions.finance_currency_id', '=', 'finance_currencies.id')
            ->select(DB::raw('SUM(finance_transactions.amount * finance_currencies.value) as total_amount'))
            ->where('is_balance' ,1)
            ->value('total_amount'); // Отримання значення суми

    }
    public function getSum(  string $value, bool $isCurrency = false, $filter = null, $start = null,$end = null ):float|string
    {
        $query = '';
        if($filter &&  isset($_GET['created_at'])  ){
            $query =  $this->query()->filters( $filter);
        }else{
            $query = $this->query()->whereBetween('created_at', [$start, $end]);

        }

        if($isCurrency){
            return  Currency::convertValueToCurrency($query->sum($value));
        }

       return $query;

    }

    public function getBalanceToBill($billId) :float
    {
        return FinanceTransaction::where('user_id', $this->getUserId())->where('finance_bill_id', $billId)->sum('amount');
    }
    public function chartPieSource($start, $end, $name=''){
        $collection = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->where('finance_transactions.user_id', $this->getUserId())
            ->where('is_balance' ,1)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $this->getType())
            ->join('finance_transaction_sources', 'finance_transactions.transaction_source_id', '=', 'finance_transaction_sources.id')
            ->select('finance_transaction_sources.id', 'finance_transaction_sources.name', DB::raw('ABS(SUM(finance_transactions.currency_amount)) as total_amount'))
            ->groupBy('finance_transaction_sources.id', 'finance_transaction_sources.name')
            ->get();
        return [[
            'name' =>  $name,
            'labels' => $collection->pluck('name')->toArray(),
            'values' => $collection->pluck('total_amount')->toArray()
        ]];
    }
    public function chartPieCategory($start, $end, $name=''){
        $collection = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->where('finance_transactions.user_id', $this->getUserId())
            ->where('is_balance' ,1)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $this->getType())
            ->join('finance_transaction_categories', 'finance_transactions.transaction_category_id', '=', 'finance_transaction_categories.id')
            ->select('finance_transaction_categories.id', 'finance_transaction_categories.name', DB::raw('ABS(SUM(finance_transactions.currency_amount)) as total_amount'))
            ->groupBy('finance_transaction_categories.id', 'finance_transaction_categories.name')
            ->get();
        return [[
            'name' =>  $name,
            'labels' => $collection->pluck('name')->toArray(),
            'values' => $collection->pluck('total_amount')->toArray()
        ]];
    }

    public function chartPieBill($start, $end,$name = ''){
        $collection = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->where('finance_transactions.user_id', $this->getUserId())
            ->where('is_balance' ,1)
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $this->getType())
            ->join('finance_bills', 'finance_transactions.finance_bill_id', '=', 'finance_bills.id')
            ->select('finance_bills.id', 'finance_bills.name',DB::raw('ABS(SUM(finance_transactions.currency_amount))  as total_amount'))
            ->groupBy('finance_bills.id', 'finance_bills.name')
            ->get();
        return [[
            'name' => $name,
            'labels' => $collection->pluck('name')->toArray(),
            'values' => $collection->pluck('total_amount')->toArray()
        ]];
    }

    public function chartPieCustomer($start, $end, $name = ''){
        $collection = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->where('finance_transactions.user_id', $this->getUserId())
            ->whereNull('finance_transactions.deleted_at')
            ->where('is_balance' ,1)
            ->join('customers', 'finance_transactions.customer_id', '=', 'customers.id')
            ->select('customers.id', 'customers.name',DB::raw('ABS(SUM(finance_transactions.currency_amount))  as total_amount'))
            ->groupBy('customers.id', 'customers.name')
            ->get();
        return [[
            'name' => $name,
            'labels' => $collection->pluck('name')->toArray(),
            'values' => $collection->pluck('total_amount')->toArray()
        ]];
    }
    public function chartBarBalance( string $name = null, string $start = null, string $end = null, string $dateColumn = null, string $value) :array
    {
        return FinanceTransaction::where('user_id', $this->getUserId())->where('is_balance' ,1)->SumByMonths($value,  $start, $end, $dateColumn)->toChart($name);
    }

    public function chartBar( string $name = null, string $start = null, string $end = null, string $dateColumn = null, string $value) :array
    {
        return $this->query()->where('is_balance' ,1)->SumByMonths($value,  $start, $end, $dateColumn)->toChart($name);
    }
    public function query(): object
    {
        return FinanceTransaction::where('type',$this->getType())->where('user_id', $this->getUserId())->where('is_balance' ,1) ;
    }

    public function getCurrency(int $bill_id, float $amount): array{
        $bill = FinanceBill::find($bill_id);
        $currency = FinanceCurrency::find($bill->finance_currency_id);
        $transaction['finance_currency_id'] = $bill->finance_currency_id;
        $transaction['currency_code'] =  $bill->currency->code;
        $transaction['currency_value'] = $currency->value;
        $transaction['currency_amount'] = $amount * $currency->value  ;
        $transaction['absolute_currency_amount'] = abs($amount * $currency->value);
        return $transaction;
    }

    public function updateStatusInvoice(int|null $invoice_id):void
    {
        if($invoice_id){
            $data = [];
            $invoice = FinanceInvoice::find($invoice_id);
            $amount_paid = FinanceTransaction::where('finance_invoice_id', $invoice_id)->sum('amount');
            $data['amount_paid'] =  $amount_paid;
            if($amount_paid >= $invoice->total){
                $data['status'] = 'paid';
            }else if($amount_paid < $invoice->total){
                $data['status'] = 'part paid';
            }else{
                $data['status'] = 'not paid';
            }
            FinanceInvoice::where('id', $invoice_id)->update($data);
        }

    }
    public function getAmountNegative(float $amount): float{
        return  -abs($amount);
    }
    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
