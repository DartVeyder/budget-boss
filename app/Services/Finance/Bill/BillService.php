<?php

namespace App\Services\Finance\Bill;

use App\Models\FinanceBill;
use App\Services\Currency\Currency;
use Illuminate\Support\Facades\Auth;

trait BillService
{
    private function generateMetricsToBill(): array{
        $data = [];

        $bills = FinanceBill::with('currency')->where('user_id', Auth::user()->id)->get();
        foreach ($bills as $bill){
             $data[  $bill->id ] =
                [
                    'name' => $bill->name,
                    'total' => ["value" => Currency::getFormatMoney($bill->transactions->sum('amount') ,$bill->currency->symbol ) ]
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
