<?php

namespace App\Services\Currency;
use App\Models\FinanceCurrency;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use function Laravel\Prompts\select;

class Currency
{
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
        if($toCurrency == 'UAH'){
            return 1;
        }
        $exchangeRates = self::parseExchangeRates();
        FinanceCurrency::where('code',$toCurrency)->update(['value'=>'44']);
        dd($currency);



        $data = array_column($exchangeRates, 'buy','ccy');
        if(!array_key_exists( $toCurrency,$data)){
            return 1;
        }
        return $data[$toCurrency];
    }

    public static function getSymbol(string $toCurrency) :string{
         $currency = FinanceCurrency::where('code',$toCurrency)->first();
         return $currency->symbol;
    }

}
