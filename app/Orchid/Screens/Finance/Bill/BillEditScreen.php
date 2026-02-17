<?php

namespace App\Orchid\Screens\Finance\Bill;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Services\Currency\Currency;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BillEditScreen extends Screen
{
    /**
     * @var FinanceBill
     */
    public $bill;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @param FinanceBill $bill
     *
     * @return array
     */
    public function query(FinanceBill $bill): iterable
    {
        return [
            'bill' => $bill,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->bill->exists ? 'Редагування рахунку' : 'Створення рахунку';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Інформація про рахунок, банківські реквізити та валюту.';
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
                ->icon('bs.check-circle')
                ->type(Color::DEFAULT)
                ->method('createOrUpdate')
                ->canSee(!$this->bill->exists),

            Button::make('Оновити')
                ->icon('bs.check-circle')
                ->type(Color::DEFAULT)
                ->method('createOrUpdate')
                ->canSee($this->bill->exists),

            Button::make('Видалити')
                ->icon('bs.trash')
                ->type(Color::DANGER)
                ->method('remove')
                ->canSee($this->bill->exists),
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
                Input::make("bill.name")
                    ->required()
                    ->title("Назва"),
                Select::make("bill.finance_currency_id")
                    ->required()
                    ->fromModel(FinanceCurrency::class, 'name')
                    ->title("Валюта"),
                Input::make("bill.bank_name")
                    ->title("Назва банку")
                    ->placeholder("Назва банку"),
                Input::make("bill.iban")
                    ->title("IBAN")
                    ->placeholder("UA..."),
            ])
        ];
    }

    /**
     * @param FinanceBill    $bill
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(FinanceBill $bill, Request $request)
    {
        $data = $request->get('bill');
        $data['currency_code'] = Currency::getCurrencyCodeWithId($data['finance_currency_id']);

        if (!$bill->exists) {
            $data['user_id'] = auth()->id();
        }

        $bill->fill($data)->save();

        Toast::info('Ви успішно створили/оновили рахунок.');

        return redirect()->route('platform.bills');
    }

    /**
     * @param FinanceBill $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove(FinanceBill $bill)
    {
        $bill->delete();

        Toast::info('Ви успішно видалили рахунок.');

        return redirect()->route('platform.bills');
    }
}
