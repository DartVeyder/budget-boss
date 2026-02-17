<?php

namespace App\Orchid\Layouts\Fop;

use App\Models\Fop;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class FopListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    public $target = 'fops';

    /**
     * Get the table columns.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('name', 'Назва')
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (Fop $fop) {
                    return Link::make($fop->name)
                        ->route('platform.fops.edit', ['fop' => $fop]);
                }),

            TD::make('ipn', 'ІПН')
                ->sort()
                ->filter(TD::FILTER_TEXT),



            TD::make('finance_bill_id', 'Рахунок')
                ->render(function (Fop $fop) {
                    return $fop->bill->name ?? 'Н/Д';
                }),

            TD::make('is_active', 'Активний')
                ->sort()
                ->render(function (Fop $fop) {
                    return $fop->is_active ? 'Так' : 'Ні';
                }),

            TD::make('created_at', 'Створено')
                ->sort()
                ->render(function (Fop $fop) {
                    return $fop->created_at->toDateTimeString();
                }),
        ];
    }
}
