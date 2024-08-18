<?php
namespace App\Orchid\Layouts\Finance\Crypto\Binance;


use App\Models\FinanceBinanceCoin;
use App\Models\FinanceTransaction;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use App\Orchid\Screens\Components\Cells\DateTime;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class CryptoBinanceListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'binanceCoins';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('ticker_symbol', __('Name')),
            TD::make('amount', __('Amount'))
            ->render(
                fn(FinanceBinanceCoin $binanceCoin) => round($binanceCoin->amount,2) . '$'
            ),
            TD::make('price', __('Price')),
            TD::make('quantity', __('Quantity')),
        ];
    }
}
