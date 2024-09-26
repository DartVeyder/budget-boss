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
    private  static array $klines = [];
    static public function client() : Spot
    {
        return new  Spot(['key' => env('API_KEY_BINANCE'), 'secret' => env('API_SECRET_KEY_BINANCE')]);
    }


    static public  function getHistoryCoin(){
        $data = [];
        $accountSnapshot  = self::accountSnapshot();
        foreach ($accountSnapshot as $key => $item){
            $row = [];
            $date = Carbon::createFromTimestampMs($item['updateTime'] );
            foreach ($item['data']['balances'] as $balance){

                if(!array_key_exists($balance['asset'], self::$klines )){
                    $klines = self::klines($balance['asset']);


                    self::$klines[$balance['asset']] =  $klines;
                }
                if( count(self::$klines[$balance['asset']]) == 0){
                    continue;
                }

                if(!array_key_exists($date->format('Y-m-d'), self::$klines[$balance['asset']])){
                    $price  = 0;
                }else{
                    $price = self::$klines[$balance['asset']][$date->format('Y-m-d')];
                }




                $row[] = [
                    'created_at' => $date->format('Y-m-d H:i:s'),
                    'updated_at' => $date->format('Y-m-d H:i:s'),
                    'ticker_symbol' =>  $balance['asset'],
                    'quantity' =>  $balance['free'],
                    'price' =>  number_format($price,6,'.',''),
                    'amount'=> round($price *  $balance['free'],7)
                ];
            }

            $data[] = $row;

        }

      return $data;
    }

    static private  function  klines($symbol   ){
         $rangeDate = self::getRangeDate();
        try {
            $klines = self::client()->klines( $symbol . 'USDT' ,'1d',$rangeDate  );
            return  collect($klines)->mapWithKeys(function ($item) {
                return [
                    Carbon::createFromTimestampMs($item[0])->format('Y-m-d') => $item[2],
                ];
            })->toArray();

        }catch(\Exception $e){
            return  [];
        }
    }

    /**
     * @return array
     */
    public static function getKlines(): array
    {
        return self::$klines;
    }

    /**
     * @param array $klines
     */
    public static function setKlines(array $klines): void
    {
        self::$klines = $klines;
    }

    static private  function  userAssets(){
        try {
            $userAsset =  self::client()->userAsset([  'needBtcValuation' => true, 'recvWindow'=> 60000]);
            $userAsset = array_map(function($item) {
                $item['free'] = $item['locked'] + $item['free'];
                return $item;
            }, $userAsset);

            return   array_column($userAsset, 'free', 'asset');
        }catch(\Exception $e){

            return  [];
        }
    }

    static  private  function  accountSnapshot()
    {
        $rangeDate = self::getRangeDate();
        try {
            $accountSnapshot =  self::client()->accountSnapshot("SPOT",[ 'recvWindow'=> 60000, 'startTime' =>  $rangeDate['startTime'],'endTime' =>$rangeDate['endTime'] ] );
           return   $accountSnapshot['snapshotVos'];
        }catch(\Exception $e){

            return  $e->getMessage();
        }
    }


    static public function getCoins():array|string
    {
        $data = [];
        $client= self::client();
        FinanceBinanceCoin::query()->update([
            'price' => 0,
            'quantity' => 0,
            'amount' => 0
        ]);
        $userAssets = self::userAssets();

            foreach($userAssets as $key => $asset){
                $quantity = (float) $asset ;

                $price = self::getCoinPrice($client,$key) ;


                $data[] = [
                    'ticker_symbol' =>  $key,
                    'quantity' =>  $quantity,
                    'price' =>  number_format($price,6,'.',''),
                    'amount'=> round($price * $quantity,7)
                ];
            }



        return $data;
    }

    static private function getRangeDate($coin_id = null)
    {
        if($coin_id){
            $binanceCoinHistories  = FinanceBinanceCoinHistory::where('id',$coin_id)->latest()->first();
        }else{
            $binanceCoinHistories  = FinanceBinanceCoinHistory::latest()->first();
        }

        $to = Carbon::now()->timestamp * 1000;
        if(!$binanceCoinHistories){
            $from = Carbon::now()->startOfMonth()->timestamp * 1000;
        }else{
            $from = (strtotime( $binanceCoinHistories->created_at) + 1) * 1000  ;
        }

        return [ 'startTime' => $from,'endTime' =>$to  ];
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
                $from = strtotime( $binanceCoinHistories->created_at) * 1000 + 1;
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
            if( $asset == "USDT" && $to == "USDT"){
                return 1;
            }
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
