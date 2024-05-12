<?php

namespace App\Services\Finance\Bill;

use App\Models\FinanceBill;
use Illuminate\Support\Facades\Auth;

trait BillService
{
    private function generateMetricsToBill(): array{
        $data = [];

        $bills = FinanceBill::where('user_id', Auth::user()->id)->get();
        foreach ($bills as $bill){
            $totalIcome =   $bill->transactions->where('type','income')->sum('amount');
            $totalExpense =  $bill->transactions->where('type','expenses')->sum('amount') ;
            $data[   $bill->id ] =
                [
                    'name' => $bill->name,
                    'income' => ["value" => number_format($totalIcome  , 0,'.',' ' ) . " ₴"],
                    'expenses' =>   ["value"=>  number_format($totalExpense  , 0,'.',' ' ) . " ₴"],
                    'total' => ["value" => number_format($totalIcome - $totalExpense  , 0,'.',' ' ). " ₴"]
                ];
        }
        return $data;
    }

    private function generateMetricsLayoutToBill():array{
        $data = [];
        $bills = FinanceBill::where('user_id', Auth::user()->id)->get();
        foreach ($bills as $bill){
            $data[$bill->name] =  "metrics.bills.". $bill->id.".total";
        }
        return $data ;
    }

}
