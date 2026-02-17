<?php

namespace App\Orchid\Screens\Customer;

use App\Models\Customer;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Illuminate\Http\Request;

class CustomerEditScreen extends Screen
{
    /**
     * @var Customer
     */
    public $customer;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Customer $customer): iterable
    {
        return [
            'customer' => $customer
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->customer->exists ? 'Редагування клієнта' : 'Створення клієнта';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Створити')
                ->icon('pencil')
                ->method('createOrUpdate')
                ->canSee(!$this->customer->exists),

            Button::make('Оновити')
                ->icon('note')
                ->method('createOrUpdate')
                ->canSee($this->customer->exists),

            Button::make('Видалити')
                ->icon('trash')
                ->method('remove')
                ->canSee($this->customer->exists),
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
            Layout::rows([
                Input::make('customer.name')
                    ->title('Ім\'я')
                    ->placeholder('Введіть ім\'я клієнта')
                    ->required(),

                Input::make('customer.email')
                    ->title('Email')
                    ->placeholder('Введіть email')
                    ->type('email'),

                Input::make('customer.phone')
                    ->title('Телефон')
                    ->placeholder('Введіть номер телефону'),

                CheckBox::make('customer.is_fop')
                    ->title('Це ФОП?')
                    ->sendTrueOrFalse(),

                Group::make([
                    Input::make('customer.ipn')
                        ->title('ІПН')
                        ->placeholder('ІПН'),

                    Input::make('customer.edrpou')
                        ->title('ЄДРПОУ')
                        ->placeholder('ЄДРПОУ (для компаній)'),
                ]),

                TextArea::make('customer.address')
                    ->title('Адреса')
                    ->placeholder('Юридична адреса')
                    ->rows(3),

                Group::make([
                    Input::make('customer.director')
                        ->title('Директор')
                        ->placeholder('ПІБ Директора'),
                ]),

                Group::make([
                    Input::make('customer.bank_name')
                        ->title('Назва банку')
                        ->placeholder('Назва банку'),

                    Input::make('customer.mfo')
                        ->title('МФО')
                        ->placeholder('МФО'),
                ]),

                Input::make('customer.iban')
                    ->title('IBAN')
                    ->placeholder('IBAN'),
            ])
        ];
    }

    /**
     * @param Customer    $customer
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(Customer $customer, Request $request)
    {
        $customer->fill($request->get('customer'));
        $customer->user_id = auth()->id(); 
        $customer->save();

        Toast::info('Клієнта успішно створено/оновлено.');

        return redirect()->route('platform.customers');
    }

    /**
     * @param Customer $customer
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove(Customer $customer)
    {
        $customer->delete();

        Toast::info('Клієнта успішно видалено.');

        return redirect()->route('platform.customers');
    }
}
