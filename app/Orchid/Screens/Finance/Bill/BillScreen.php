<?php

namespace App\Orchid\Screens\Finance\Bill;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceTransactionCategory;
use App\Orchid\Layouts\Finance\Bill\BillListLayout;
use App\Orchid\Layouts\Finance\Transaction\Category\CategoryRows;
use App\Services\Currency\Currency;
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
        return 'Рахунки';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            \Orchid\Screen\Actions\Link::make(__('Додати'))
                ->icon('bs.plus-circle')
                ->route('platform.bills.create'),
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
            Layout::view('finance.bill.index'),
        ];
    }

    public function remove(Request $request)
    {
        FinanceBill::findOrFail($request->get('id'))->delete();
        Toast::info(__('Успішно видалено'));
        return redirect()->route('platform.bills');
    }
}
