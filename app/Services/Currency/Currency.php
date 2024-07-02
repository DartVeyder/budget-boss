<?php

namespace App\Services\Currency;
use App\Models\FinanceCurrency;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use function Laravel\Prompts\select;

class Currency
{
    private  static string $symbol;

    private static string $urlApi = 'https://api.privatbank.ua/p24api/pubinfo?exchange&coursid=5';
    public static  function  parseExchangeRates(){
        $client = new Client();

        try {
            $response = $client->get(self::$urlApi);

            $data = $response->getBody()->getContents();
           return json_decode($data) ;
        } catch (\Exception $e) {
            // Обробка помилок
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public static function getExchangeRate(string $toCurrency) :float|null{
        $currency = FinanceCurrency::where('code',$toCurrency)->first();

        self::$symbol =  $currency->symbol;

        if($toCurrency == 'UAH'){
            return 1;
        }

        if(self::isSameAsCurrentDate( $currency->updated_at)){
            return $currency->value;
        }

        $exchangeRates = self::parseExchangeRates();

        $data = array_column($exchangeRates, 'buy','ccy');

        if(!array_key_exists( $toCurrency,$data)){
            return 1;
        }

        $value =  $data[$toCurrency];

        FinanceCurrency::where('code',$toCurrency)->update(['value'=>$value]);

        return $value;
    }
    public  static function getFormatMoney( $value , $symbol = ''): string{
        if(!$symbol){
            $symbol  = self::$symbol;
        }
        return number_format($value  , 0,'.',' ' ) . ' '.$symbol;
    }

    public static  function  convertValueToCurrency(float $value, $isFormatMoney = true) :float|string{
        $exchangeRate = self::getExchangeRate(self::getCurrencyCodeUser());
        $value =  $value / $exchangeRate;

        if($isFormatMoney){
            $value =  self::getFormatMoney( $value);
        }

        return $value;
    }

    public  static  function  getCurrencyCodeUser():string{
       $userSetting = Auth::user()->setting;
       return $userSetting->currency;
    }

    private  static function isSameAsCurrentDate(string $date):bool{
        $date = Carbon::parse($date);
        $currentDate = Carbon::now();
        if ($date->isSameDay($currentDate)) {
            return true;
        } else {
           return false;
        }
    }


}
