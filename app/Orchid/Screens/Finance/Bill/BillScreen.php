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
    public function query(Request $request): iterable
    {
        $filterUserId = $request->get('user_id');
        $filterUserId = ($filterUserId && is_numeric($filterUserId)) ? (int)$filterUserId : null;

        return [
            "bills" => $this->generateMetricsToBill($filterUserId),
            "groupedData" => $this->getGroupedBillsData($filterUserId),
            "filter_user_id" => $filterUserId,
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

    public function permission(): ?iterable
    {
        return [
            'platform.finance.bills',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $actions = [];

        if (Auth::user()?->isSharingBudget()) {
            $filterUserId = request()->get('user_id');
            $activeLabel = 'Всі рахунки (Спільно)';
            if ($filterUserId) {
                $target = \App\Models\User::find($filterUserId);
                $activeLabel = $target ? ($target->id === Auth::id() ? "Мої рахунки (Я)" : "Рахунки: {$target->name}") : $activeLabel;
            }

            $userLinks = [
                \Orchid\Screen\Actions\Link::make('Всі рахунки (Спільно)')
                    ->icon('bs.people')
                    ->route('platform.bills'),
            ];

            foreach (Auth::user()->getSharedUsers() as $u) {
                $lbl = $u->id === Auth::id() ? "Мої рахунки ({$u->name})" : "Рахунки: {$u->name}";
                $userLinks[] = \Orchid\Screen\Actions\Link::make($lbl)
                    ->icon('bs.person')
                    ->route('platform.bills', ['user_id' => $u->id]);
            }

            $actions[] = \Orchid\Screen\Actions\DropDown::make('Фільтр: ' . $activeLabel)
                ->icon('bs.funnel')
                ->list($userLinks);
        }

        $actions[] = \Orchid\Screen\Actions\Link::make(__('Додати'))
            ->icon('bs.plus-circle')
            ->route('platform.bills.create');

        return $actions;
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
