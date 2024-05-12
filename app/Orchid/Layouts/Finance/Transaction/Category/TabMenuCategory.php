<?php

namespace App\Orchid\Layouts\Finance\Transaction\Category;

use Orchid\Screen\Actions\Menu;
use Orchid\Screen\Layouts\TabMenu;

class TabMenuCategory extends TabMenu
{
    /**
     * Get the menu elements to be displayed.
     *
     * @return Menu[]
     */
    protected function navigations(): iterable
    {
        return [
            Menu::make('Income')
                ->route('platform.transactions.categories.income'),
            Menu::make('Expenses')
                ->route('platform.transactions.categories.expenses'),
        ];
    }
}
