<?php

namespace App\Orchid\Screens\Finance\Bill;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceTransactionCategory;
use App\Orchid\Layouts\Finance\Bill\BillListLayout;
use App\Orchid\Layouts\Finance\Transaction\Category\CategoryRows;
use App\Services\Finance\Bill\BillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class BillScreen extends Screen
{
    use BillService;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            "bills" => $this->generateMetricsToBill()
            ];

    }
    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Bills';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Add'))
                ->modal('addBill')
                ->method('save'),
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
            Layout::view('finance.bill.bills'),
            Layout::modal('addBill', [
                Layout::rows([
                    Input::make("name")
                        ->required()
                        ->title("Name"),
                    Select::make("finance_currency_id")
                        ->required()
                        ->fromModel(FinanceCurrency::class, 'name')
                        ->title("Currency"),
                    Input::make('user_id')
                        ->value(Auth::user()->id)
                        ->hidden(),
                ])
            ])
                ->applyButton(__('Save'))
                ->title(__('New bill')),
        ];
    }

    public function save(Request $request, FinanceBill $bill){
        $bill->fill($request->all())->save();
        Toast::info(__('You have successfully created.'));
    }

    public function remove(Request $request)
    {
        FinanceBill::findOrFail($request->get('id'))->delete();
        Toast::info(__('You have successfully remove'));
        return redirect()->route('platform.bills');
    }
}
