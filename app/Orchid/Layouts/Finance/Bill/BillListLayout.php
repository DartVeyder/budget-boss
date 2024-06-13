<?php
namespace App\Orchid\Layouts\Finance\Bill;


use App\Models\FinanceTransaction;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use App\Orchid\Screens\Components\Cells\DateTime;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class BillListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'bills';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('transaction_category_id', __('Category'))
                ->render(
                    fn(FinanceTransaction $transaction) => ($transaction->category)? $transaction->category->name : ''
                ),
            TD::make('Bill', __('Bill'))
                ->render(
                fn(FinanceTransaction $transaction) => $transaction->bill->name
            ),
            TD::make('amount', __('Amount'))
                ->render(function ($transaction){
                    return view('finance.transaction.partials.amount', $transaction );
                }),

            TD::make('created_at', __('Created'))
                ->usingComponent(DateTime::class)
                ->align(TD::ALIGN_RIGHT)
                ->sort(),
            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (FinanceTransaction $transaction) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
//                        Link::make(__('View'))
//                            ->route('platform.transactions.card', $transaction->id)
//                            ->icon('bs.eye'),
//                        Link::make(__('Edit'))
//                            ->route('platform.transactions.edit', $transaction->id)
//                            ->icon('bs.pencil'),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->method('remove', [
                                'id' => $transaction->id,
                            ]),
                    ])),
        ];
    }
}
