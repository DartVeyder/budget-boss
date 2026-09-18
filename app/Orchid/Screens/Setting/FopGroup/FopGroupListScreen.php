<?php

namespace App\Orchid\Screens\Setting\FopGroup;

use App\Models\FopGroup;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\TD;

class FopGroupListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'fopGroups' => FopGroup::with('taxRates')->get(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Групи ФОП';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Додати групу')
                ->icon('bs.plus-circle')
                ->route('platform.setting.fop-groups.create'),
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
            Layout::table('fopGroups', [
                TD::make('name', 'Назва групи'),
                TD::make('taxes', 'Податки')
                    ->render(function (FopGroup $fopGroup) {
                        return $fopGroup->taxRates->map(function ($taxRate) {
                            return "{$taxRate->name} ({$taxRate->value}%)";
                        })->implode(', ');
                    }),
                TD::make('annual_limit', 'Річний ліміт')
                    ->render(fn (FopGroup $fopGroup) => $fopGroup->annual_limit ? number_format($fopGroup->annual_limit, 2, '.', ' ') . ' ₴' : '—'),
                TD::make('monthly_esv', 'Щомісячний ЄСВ')
                    ->render(fn (FopGroup $fopGroup) => $fopGroup->monthly_esv ? number_format($fopGroup->monthly_esv, 2, '.', ' ') . ' ₴' : '—'),
                TD::make('Actions')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(fn (FopGroup $fopGroup) => 
                        Link::make('Редагувати')
                            ->route('platform.setting.fop-groups.edit', ['fopGroup' => $fopGroup])
                            ->icon('bs.pencil')
                    ),
            ]),
        ];
    }
}
