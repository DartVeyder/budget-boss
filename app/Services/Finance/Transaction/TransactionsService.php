<?php

namespace App\Services\Finance\Transaction;

use App\Models\FinanceTransaction;
use App\Services\Currency\Currency;
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
        $query = FinanceTransaction::where('user_id', $this->getUserId()) ;;
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

    public function chartPieCategory($start, $end){
        $collection = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->where('finance_transactions.user_id', $this->getUserId())
            ->whereNull('finance_transactions.deleted_at')
            ->where('finance_transactions.type', $this->getType())
            ->join('finance_transaction_categories', 'finance_transactions.transaction_category_id', '=', 'finance_transaction_categories.id')
            ->select('finance_transaction_categories.id', 'finance_transaction_categories.name', DB::raw('ABS(SUM(finance_transactions.currency_amount)) as total_amount'))
            ->groupBy('finance_transaction_categories.id', 'finance_transaction_categories.name')
            ->get();
        return [[
            'name' => '',
            'labels' => $collection->pluck('name')->toArray(),
            'values' => $collection->pluck('total_amount')->toArray()
        ]];
    }

    public function chartPieBill($start, $end,$name = ''){
        $collection = DB::table('finance_transactions')
            ->whereBetween('finance_transactions.created_at', [$start, $end])
            ->where('finance_transactions.user_id', $this->getUserId())
            ->whereNull('finance_transactions.deleted_at')
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
        return FinanceTransaction::where('user_id', $this->getUserId())->SumByMonths($value,  $start, $end, $dateColumn)->toChart($name);
    }

    public function chartBar( string $name = null, string $start = null, string $end = null, string $dateColumn = null, string $value) :array
    {
        return $this->query()->SumByMonths($value,  $start, $end, $dateColumn)->toChart($name);
    }
    public function query(): object
    {
        return FinanceTransaction::where('type',$this->getType())->where('user_id', $this->getUserId()) ;
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
