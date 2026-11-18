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
        if ($bill->exists && $bill->user_id !== auth()->id() && !auth()->user()->canEditSharedBudgetWith($bill->user_id)) {
            abort(403);
        }

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

    public function permission(): ?iterable
    {
        return [
            'platform.finance.bills',
        ];
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
        $sharedUsers = auth()->user()?->isSharingBudget()
            ? auth()->user()->getSharedUsers()->pluck('name', 'id')->toArray()
            : [];

        return [
            Layout::rows([
                Select::make("bill.user_id")
                    ->title("Власник рахунку")
                    ->options($sharedUsers)
                    ->value($this->bill->exists ? $this->bill->user_id : (int)request()->get('user_id', auth()->id()))
                    ->canSee(auth()->user()?->isSharingBudget() && !$this->bill->exists)
                    ->help("Оберіть користувача, якому належатиме рахунок."),

                Input::make("bill.name")
                    ->required()
                    ->title("Назва"),

                Select::make("bill.finance_currency_id")
                    ->required()
                    ->fromModel(FinanceCurrency::class, 'name')
                    ->title("Валюта"),

                Input::make("initial_balance")
                    ->title("Початковий баланс / сума")
                    ->type('number')
                    ->step(0.01)
                    ->placeholder("0.00")
                    ->canSee(!$this->bill->exists)
                    ->help("Початкова сума коштів на рахунку на момент старту обліку (створить транзакцію ревізії)."),

                Input::make("bill.bank_name")
                    ->title("Назва банку")
                    ->placeholder("Назва банку"),

                Input::make("bill.iban")
                    ->title("IBAN")
                    ->placeholder("UA..."),

                Input::make("bill.monobank_account_id")
                    ->title("Monobank Account ID")
                    ->placeholder("0 для особистої картки або ID рахунку з Monobank")
                    ->help("Визначається автоматично за IBAN або типом рахунку."),
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
        $targetUserId = auth()->id();
        if (auth()->user()?->isSharingBudget() && $request->filled('bill.user_id')) {
            $requestedUserId = (int)$request->input('bill.user_id');
            if (in_array($requestedUserId, auth()->user()->getSharedUserIds(), true)) {
                $targetUserId = $requestedUserId;
            }
        }

        if ($bill->exists && $bill->user_id !== auth()->id() && !auth()->user()->canEditSharedBudgetWith($bill->user_id)) {
            abort(403);
        }

        $isNew = !$bill->exists;
        $data = $request->get('bill');
        $data['currency_code'] = Currency::getCurrencyCodeWithId($data['finance_currency_id']);
        if ($isNew) {
            $data['user_id'] = $targetUserId;
        }

        $bill->fill($data)->save();

        if ($isNew && $request->filled('initial_balance')) {
            $initialBalance = (float)$request->input('initial_balance');
            if ($initialBalance != 0) {
                $transactionsService = app(\App\Services\Finance\Transaction\TransactionsService::class);
                $currencyData = $transactionsService->getCurrency($bill->id, $initialBalance);

                \App\Models\FinanceTransaction::create(array_merge([
                    'user_id' => $bill->user_id,
                    'finance_bill_id' => $bill->id,
                    'transaction_type_id' => 4, // Ревізія
                    'transaction_category_id' => 2, // Ревізія
                    'type' => $initialBalance > 0 ? 'income' : 'expenses',
                    'amount' => $initialBalance,
                    'balance' => $initialBalance,
                    'balance_bill' => $initialBalance,
                    'is_balance' => 1,
                    'comment' => 'Початковий баланс',
                    'accrual_date' => now(),
                    'created_at' => now(),
                ], $currencyData));
            }
        }

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
        if ($bill->exists && $bill->user_id !== auth()->id()) {
            abort(403);
        }

        $bill->delete();

        Toast::info('Ви успішно видалили рахунок.');

        return redirect()->route('platform.bills');
    }
}
