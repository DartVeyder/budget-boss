<?php

namespace App\Services\Finance\Crypto\Binance;

use App\Models\FinanceBinanceCoin;
use App\Services\Currency\Currency;
use Binance\Spot;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class BinanceService
{
    static public function getCoins():array
    {
        $data = [];
        $client = new  Spot(['key' => env('API_KEY_BINANCE'), 'secret' => env('API_SECRET_KEY_BINANCE')]);
        $coins = $client->userAsset( );
        foreach($coins as $coin){
            $price = self::getCoinPrice($client, $coin['asset']) ;
            $quantity = (float) $coin['free'];
            $data[] = [
                'ticker_symbol' =>  $coin['asset'],
                'quantity' =>  $quantity,
                'price' =>  number_format($price,6,'.',''),
                 'amount'=> round($price * $quantity,7)
            ];
        }
        return $data;
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
        $client = new  Spot(['key' => env('API_KEY_BINANCE'), 'secret' => env('API_SECRET_KEY_BINANCE')]);
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
