<?php

namespace App\Orchid\Screens\Setting\TaxRate;

use App\Models\TaxRate;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;

class TaxRateListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'taxRates' => TaxRate::all(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Податкові ставки';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Додати ставку')
                ->icon('bs.plus-circle')
                ->route('platform.setting.tax-rates.create'),
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
            Layout::table('taxRates', [
                TD::make('name', 'Назва'),
                TD::make('value', 'Значення (%)')
                    ->render(fn (TaxRate $taxRate) => $taxRate->value . '%'),
                TD::make('Actions')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(fn (TaxRate $taxRate) => 
                        Link::make('Редагувати')
                            ->route('platform.setting.tax-rates.edit', $taxRate)
                            ->icon('bs.pencil')
                    ),
            ]),
        ];
    }
}
