<?php

namespace App\Orchid\Layouts\Finance\Transaction;

use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\FinanceBill;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\TaxRate;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Layouts\Listener;
use Orchid\Support\Facades\Layout;

class TransactionIncomeListener extends Listener
{
    /**
     * List of field names for which values will be listened.
     *
     * @var string[]
     */
    protected $targets = [
        'transaction.customer_id',
        'transaction.counterparty_id',
    ];

    /**
     * What screen method should be called
     * as a source for an asynchronous request.
     *
     * @var string
     */
    protected $asyncMethod = 'asyncGetCustomerDefaults';

    /**
     * Update the repository with the listener's targets.
     *
     * @param \Orchid\Screen\Repository $repository
     * @param \Illuminate\Http\Request  $request
     *
     * @return \Orchid\Screen\Repository
     */
    public function handle(\Orchid\Screen\Repository $repository, \Illuminate\Http\Request $request): \Orchid\Screen\Repository
    {
        $customerId = $request->input('transaction.customer_id');
        $counterpartyId = $request->input('transaction.counterparty_id');

        $transaction = $repository->get('transaction', []);
        if ($transaction instanceof FinanceTransaction) {
            $transaction = $transaction->toArray();
        }

        // If counterparty was selected but no customer yet, auto-set customer from counterparty
        if ($counterpartyId && !$customerId) {
            $cp = CustomerCounterparty::find($counterpartyId);
            if ($cp?->customer_id) {
                $customerId = $cp->customer_id;
            }
        }

        $customer = $customerId ? Customer::with(['fop.fopGroup.taxRates', 'counterparties' => fn ($q) => $q->active()])->find($customerId) : null;

        $transaction['customer_id'] = $customerId;
        if ($customer?->fop?->finance_bill_id) {
            $transaction['finance_bill_id'] = $customer->fop->finance_bill_id;
        }
        if ($customer?->fop?->transaction_category_id) {
            $transaction['transaction_category_id'] = $customer->fop->transaction_category_id;
        }

        // Resolve counterparty: prioritize explicit selection matching customer, or auto-pick if customer has exactly 1 counterparty
        if ($counterpartyId && $customer && $customer->counterparties->pluck('id')->contains((int)$counterpartyId)) {
            $transaction['counterparty_id'] = (int)$counterpartyId;
        } elseif ($customer && $customer->counterparties->count() === 1) {
            $transaction['counterparty_id'] = $customer->counterparties->first()->id;
        } else {
            $currentCpId = $transaction['counterparty_id'] ?? null;
            if ($currentCpId && $customer && !$customer->counterparties->pluck('id')->contains((int)$currentCpId)) {
                $transaction['counterparty_id'] = null;
            }
        }

        $repository->set('transaction', $transaction);
        if ($customer?->fop?->tax_status) {
            $repository->set('tax_status', $customer->fop->tax_status);
        }
        if ($customer?->fop?->fopGroup?->taxRates) {
            $repository->set('tax_rates', $customer->fop->fopGroup->taxRates->pluck('id')->toArray());
        }

        return $repository;
    }

    /**
     * @return Layout[]
     */
    protected function layouts(): iterable
    {
        $customerId = null;
        $selectedCounterpartyId = null;

        if ($this->query) {
            $customerId = $this->query->get('transaction.customer_id');
            $selectedCounterpartyId = $this->query->get('transaction.counterparty_id');

            $tx = $this->query->get('transaction');
            if (is_array($tx)) {
                $customerId = $customerId ?: ($tx['customer_id'] ?? null);
                $selectedCounterpartyId = $selectedCounterpartyId ?: ($tx['counterparty_id'] ?? null);
            } elseif ($tx instanceof FinanceTransaction) {
                $customerId = $customerId ?: $tx->customer_id;
                $selectedCounterpartyId = $selectedCounterpartyId ?: $tx->counterparty_id;
            }
        }

        $counterpartyOptions = [];
        $helpText = 'Якщо оплата надійшла від конкретного ФОП контрагента клієнта';

        if ($customerId) {
            $counterparties = CustomerCounterparty::where('customer_id', $customerId)
                ->active()
                ->get();

            foreach ($counterparties as $cp) {
                $counterpartyOptions[$cp->id] = $cp->full_title;
            }

            if (empty($counterpartyOptions)) {
                $helpText = 'У цього замовника немає зареєстрованих ФОП контрагентів';
            } elseif (count($counterpartyOptions) === 1) {
                $helpText = '✓ ФОП контрагента автоматично підтягнуто з профілю замовника';
            }
        } else {
            $sharedIds = \Illuminate\Support\Facades\Auth::user()?->getSharedUserIds() ?? [\Illuminate\Support\Facades\Auth::id()];
            $allCounterparties = CustomerCounterparty::whereIn('user_id', $sharedIds)
                ->active()
                ->with('customer')
                ->get();

            foreach ($allCounterparties as $cp) {
                $custName = $cp->customer?->name ? " [{$cp->customer->name}]" : '';
                $counterpartyOptions[$cp->id] = $cp->name . $custName . ($cp->ipn ? " (ІПН: {$cp->ipn})" : '');
            }
        }

        $sharedUserIds = \Illuminate\Support\Facades\Auth::user()?->getSharedUserIds() ?? [\Illuminate\Support\Facades\Auth::id()];
        $userOptions = count($sharedUserIds) > 1
            ? \App\Models\User::whereIn('id', $sharedUserIds)->pluck('name', 'id')->toArray()
            : [];

        return [
            Layout::rows([
                Select::make('transaction.user_id')
                    ->title('Користувач')
                    ->options($userOptions)
                    ->value(\Illuminate\Support\Facades\Auth::id())
                    ->help('Хто з учасників бюджету отримує дохід')
                    ->canSee(count($sharedUserIds) > 1),

                Relation::make('transaction.transaction_category_id')
                    ->title('Category')
                    ->required()
                    ->fromModel(FinanceTransactionCategory::class, 'name')
                    ->applyScope('income'),

                Relation::make('transaction.finance_invoice_id')
                    ->title('№ Invoice')
                    ->applyScope('user')
                    ->fromModel(FinanceInvoice::class, 'invoice_number'),

                Relation::make('transaction.customer_id')
                    ->title('From whom')
                    ->fromModel(Customer::class, 'name')
                    ->applyScope('user'),

                Select::make('transaction.counterparty_id')
                    ->title('ФОП контрагент (платник)')
                    ->options($counterpartyOptions)
                    ->empty('Не обрано (оплата від основного клієнта)', '')
                    ->value($selectedCounterpartyId)
                    ->help($helpText),

                Relation::make('transaction.finance_bill_id')
                    ->title('Bills')
                    ->required()
                    ->displayAppend('billCurrency')
                    ->fromModel(FinanceBill::class, 'name')
                    ->applyScope('user'),

                Input::make("transaction.amount")
                    ->title('Top-up amount')
                    ->required()
                    ->step(0.01)
                    ->type('number'),

                Select::make("tax_status")
                    ->options([
                        'without_taxes' => 'без податків',
                        'after_taxes'   => 'після сплати податків',
                        'before_taxes'  => 'до сплати податків'
                    ])
                    ->empty('без податків', 'without_taxes')
                    ->title('Tax status'),

                Relation::make("tax_rates")
                    ->fromModel(TaxRate::class, 'name')
                    ->multiple()
                    ->title('Tax rate'),

                DateTimer::make('transaction.accrual_date')
                    ->format24hr()
                    ->title('Date accrual'),

                DateTimer::make('transaction.created_at')
                    ->title('Date created')
                    ->enableTime()
                    ->format24hr(),

                TextArea::make("transaction.comment")
                    ->title('Comment')
                    ->value(''),

                Upload::make('transaction.attachment')
                    ->title('Документи / Зображення'),

                Input::make('transaction.transaction_type_id')
                    ->value(2)
                    ->hidden(),

                Input::make('transaction.type')
                    ->value('income')
                    ->hidden()
            ]),
        ];
    }
}
