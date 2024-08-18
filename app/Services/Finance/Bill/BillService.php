<?php

namespace App\Services\Finance\Bill;

use App\Models\FinanceBill;
use App\Services\Currency\Currency;
use App\Services\Finance\Crypto\Binance\BinanceService;
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
        $data['binance'] = BinanceService::getBill();
        return $data;
    }

    private function generateMetricsLayoutToBill($max = 0):array{
        $data = [];
        $bills = FinanceBill::where('user_id', Auth::user()->id)->get();

        foreach ($bills as $key => $bill){
            $data[$bill->name] =  "metrics.bills.". $bill->id.".total";
            if($max   == $key && $max != 0 ){
                break;
            }
        }
        return $data ;
    }

}
