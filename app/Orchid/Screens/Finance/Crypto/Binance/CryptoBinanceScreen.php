<?php

namespace App\Orchid\Screens\Finance\Crypto\Binance;

use App\Models\FinanceBinanceCoin;
use App\Models\FinanceBinanceCoinHistory;
use App\Orchid\Layouts\Finance\Crypto\Binance\CryptoBinanceListLayout;
use App\Services\Currency\Currency;
use App\Services\Finance\Crypto\Binance\BinanceService;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class CryptoBinanceScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {


        return [
            "balance" =>  Currency::getFormatMoney(BinanceService::getBalanceUSDT(), "$"),
            "binanceCoins" => FinanceBinanceCoin::
                 defaultSort('amount', 'desc')
                ->paginate()
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Binance';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Import')
            ->method('import')
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Balance' => 'balance'
                ]),
            CryptoBinanceListLayout::class
        ];
    }

    public function import(FinanceBinanceCoin $binanceCoin)
    {
        $msg = [];

        $coins = BinanceService::getCoins();
        if(!is_array( $coins)){
            $msg[] = __('Помилка оновленя даних з Binance');
        }

        foreach ($coins as $coin){
             $binanceCoin = $binanceCoin->updateOrCreate( [    'ticker_symbol' => $coin['ticker_symbol']  ], $coin );
        }
        if( $binanceCoin ){
            $msg[] = __('Успішно оновлено дані з Binance');
        }

        if(!is_array( $coins)){
            $msg[] = __('Помилка оновленя даних з Binance');
        }

        $historyCoins = BinanceService::getHistoryCoin();
        if( empty($historyCoins)){
            $msg[] = __('Відсутні нові дані з Binance');
        }

        foreach ($historyCoins as $coin){
             FinanceBinanceCoinHistory::insert($coin);
        }




        Toast::info(implode(", ",  $msg) );
        return;
    }
}
