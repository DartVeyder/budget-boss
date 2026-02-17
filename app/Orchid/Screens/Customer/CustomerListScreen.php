<?php

namespace App\Orchid\Screens\Customer;

use App\Models\Customer;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\TD;

class CustomerListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'customers' => Customer::user()->filters()->defaultSort('id', 'desc')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Клієнти';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Додати нового')
                ->icon('pencil')
                ->route('platform.customers.create'),
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
            Layout::table('customers', [
                TD::make('name', 'Ім\'я')
                    ->sort()
                    ->render(function (Customer $customer) {
                        return Link::make($customer->name)
                            ->route('platform.customers.edit', ['customer' => $customer->id]);
                    }),

                TD::make('email', 'Email')
                    ->sort(),

                TD::make('phone', 'Телефон')
                    ->sort(),
                
                TD::make('ipn', 'ІПН')
                    ->sort(),

                TD::make('is_fop', 'ФОП')
                    ->sort()
                    ->render(function (Customer $customer) {
                        return $customer->is_fop ? 'Так' : 'ні';
                    }),

                TD::make('created_at', 'Створено')
                    ->sort()
                    ->render(function (Customer $customer) {
                        return $customer->created_at->toDateTimeString();
                    }),
            ])
        ];
    }
}
