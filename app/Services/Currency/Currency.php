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
    private static string $symbol = '₴';

    private static string $urlApi = 'https://api.privatbank.ua/p24api/pubinfo?exchange&coursid=5';

    public static function parseExchangeRates(): array
    {
        $client = new Client(['timeout' => 5]);

        try {
            $response = $client->get(self::$urlApi);
            $data = $response->getBody()->getContents();
            $decoded = json_decode($data);
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function updateExchangeRates(array $toCurrencies = []): void
    {
        foreach ($toCurrencies as $currency) {
            self::getExchangeRate($currency);
        }
    }

    public static function getExchangeRate(string $toCurrency): float|null
    {
        $currency = FinanceCurrency::where('code', $toCurrency)->first();

        if ($currency) {
            self::$symbol = $currency->symbol;
        }

        if ($toCurrency === 'UAH') {
            return 1;
        }

        if (!$currency) {
            return 1;
        }

        if ($currency->updated_at && self::isSameAsCurrentDate((string)$currency->updated_at)) {
            return (float)$currency->value;
        }

        $exchangeRates = self::parseExchangeRates();
        if (empty($exchangeRates)) {
            return (float)($currency->value ?? 1);
        }

        $data = array_column($exchangeRates, 'buy', 'ccy');

        if (!array_key_exists($toCurrency, $data)) {
            return (float)($currency->value ?? 1);
        }

        $value = (float)$data[$toCurrency];

        FinanceCurrency::where('code', $toCurrency)->update(['value' => $value]);

        return $value;
    }
    public  static function getFormatMoney( $value , $symbol = ''): string{
        if(!$symbol){
            $symbol  = self::$symbol;
        }
        return number_format($value  , 0,'.',' ' ) . ' '.$symbol;
    }

    public static  function  convertValueToCurrency(float|int|null $value = 0, $isFormatMoney = true) :float|string{
        $exchangeRate = self::getExchangeRate(self::getCurrencyCodeUser());
        $value =  $value / $exchangeRate;

        if($isFormatMoney){
            $value =  self::getFormatMoney( $value);
        }

        return $value;
    }

    public static  function getCurrencyCodeWithId(int $id): string|null{
        $currency = FinanceCurrency::find($id);
        if( !$currency){
            return  null;
        }
        return $currency->code;
    }

    public static function getCurrencyCodeUser(): string
    {
        $userSetting = Auth::user()?->setting;
        return $userSetting?->currency ?? 'UAH';
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
