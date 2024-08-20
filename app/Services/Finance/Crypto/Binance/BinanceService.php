<?php

namespace App\Services\Finance\Crypto\Binance;

use App\Models\FinanceBinanceCoin;
use App\Models\FinanceBinanceCoinHistory;
use App\Services\Currency\Currency;
use Binance\Spot;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class BinanceService
{
    static public function client() : Spot
    {
        return new  Spot(['key' => env('API_KEY_BINANCE'), 'secret' => env('API_SECRET_KEY_BINANCE')]);
    }
    static public function getCoins():array|string
    {
        $data = [];
        $client= self::client();

            $coins = $client->userAsset(['timestamp' => time() * 1000]);

            foreach($coins as $coin){
                $quantity = (float) $coin['free'];

                $price = self::getCoinPrice($client, $coin['asset']) ;

                $data[] = [
                    'ticker_symbol' =>  $coin['asset'],
                    'quantity' =>  $quantity,
                    'price' =>  number_format($price,6,'.',''),
                    'amount'=> round($price * $quantity,7)
                ];
            }




        return $data;
    }



    static public function getCoinsHistory()
    {

        $client = self::client();


        $binanceCoins = FinanceBinanceCoin::all();
        foreach ( $binanceCoins as $coin){
            $binanceCoinHistories  = FinanceBinanceCoinHistory::where('id',$coin->id)->latest()->first();
            $to = Carbon::now()->timestamp * 1000;
            if(!$binanceCoinHistories){
                $from = Carbon::now()->startOfYear()->timestamp * 1000;
            }else{
                $from = strtotime( $binanceCoinHistories->created_at) *1000 + 1;
            }
            $data = [];
            try {
                $clines =  $client->klines( $coin->ticker_symbol . 'USDT' ,'1d', [ 'startTime' => $from,'endTime' =>$to  ] );

            }catch(\Exception $e){
                $clines =  [];
            }

            if(is_array( $clines )) {
                foreach ($clines as $cline) {
                    $price = $cline[2];
                    $data[] = [
                        'binance_coin_id' => $coin->id,
                        'ticker_symbol' => $coin->ticker_symbol,
                        'quantity' => $coin->quantity,
                        'price' => number_format($price, 6, '.', ''),
                        'amount' => round($price * $coin->quantity, 7),
                        'created_at' => self::getDate($cline[0]),
                        'updated_at' => self::getDate($cline[0])

                    ];
                }
            }
            FinanceBinanceCoinHistory::insert($data);

        }


        return $data;

    }

    static private function getDate($time):string
    {
        $time = $time / 1000;
        $carbonDate = Carbon::createFromTimestamp($time);
        return $carbonDate->format('Y-m-d H:i:s');

    }

    static private function getInsertData($price, $symbol, $quantity){
        $quantity = (float) $quantity;
        return [
            'ticker_symbol' =>  $symbol,
            'quantity' =>  $quantity,
            'price' =>  number_format($price,6,'.',''),
            'amount'=> round($price * $quantity,7)
        ];
    }


    static function getCoinPrice($client, $asset, $to = 'USDT'):float
    {
        try {
            $tickerPrice = $client->tickerPrice(['symbol'=> $asset."" . $to ] );
            return $tickerPrice['price'];

        }catch(\Exception $e){
            return 0;
        }

    }

    static public function getBalanceUSDT()
    {
        return FinanceBinanceCoin::sum('amount');
    }

    static public function getBalanceUAH()
    {
        $client= self::client();
        return round(self::getBalanceUSDT() * self::getCoinPrice($client, "USDT","UAH") ,2);
    }

    static public function getBill()
    {
        return [
            "name" => 'Binance',
            "total" => ['value' => Currency::getFormatMoney(self::getBalanceUSDT(), '$')]
        ];
    }
}
