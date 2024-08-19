<?php

namespace App\Orchid\Screens\Finance\Crypto\Binance;

use App\Models\FinanceBinanceCoin;
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

        $coins = BinanceService::getCoins();
        if(!is_array( $coins)){
            Toast::info(__('Помилка оновленя даних з Binance'));
            return;
        }
        foreach ($coins as $coin){
            $binanceCoin->updateOrCreate(
                [
                    'ticker_symbol' => $coin['ticker_symbol']
                ],
                $coin
            );
        }

        Toast::info(__('Успішно оновлено дані з Binance'));
    }
}
