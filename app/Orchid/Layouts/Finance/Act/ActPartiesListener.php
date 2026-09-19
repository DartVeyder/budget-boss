<?php

namespace App\Orchid\Layouts\Finance\Act;

use App\Models\Act;
use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\Fop;
use Illuminate\Http\Request;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class ActPartiesListener extends Listener
{
    /**
     * List of field names for which values will be listened.
     *
     * @var string[]
     */
    protected $targets = [
        'act.fop_id',
        'act.customer_id',
        'act.counterparty_id',
    ];

    /**
     * What screen method should be called
     * as a source for an asynchronous request.
     *
     * @var string
     */
    protected $asyncMethod = 'asyncGetCustomerParties';

    /**
     * Update the repository with the listener's targets.
     */
    public function handle(Repository $repository, Request $request): Repository
    {
        $fopId = $request->input('act.fop_id');
        $customerId = $request->input('act.customer_id');
        $counterpartyId = $request->input('act.counterparty_id');
        $customer = $customerId ? Customer::with(['fop', 'counterparties' => fn ($q) => $q->active()])->find($customerId) : null;

        $act = $repository->get('act', []);
        if ($act instanceof Act) {
            $act = $act->toArray();
        }

        if ($customerId) {
            $act['customer_id'] = $customerId;
        }
        if ($fopId) {
            $act['fop_id'] = $fopId;
        }

        // Auto-select counterparty if customer has exactly 1 counterparty
        $counterparty = null;
        if ($customer && $customer->counterparties->count() === 1) {
            $counterparty = $customer->counterparties->first();
            $act['counterparty_id'] = $counterparty->id;
        } else {
            $currentCpId = $counterpartyId ?: ($act['counterparty_id'] ?? null);
            if ($currentCpId && $customer && !$customer->counterparties->pluck('id')->contains($currentCpId)) {
                $act['counterparty_id'] = null;
            } elseif ($currentCpId && $customer) {
                $counterparty = $customer->counterparties->firstWhere('id', $currentCpId);
                $act['counterparty_id'] = $currentCpId;
            } elseif ($currentCpId) {
                $counterparty = CustomerCounterparty::find($currentCpId);
                $act['counterparty_id'] = $currentCpId;
            }
        }

        // Auto-fill address and phone from counterparty or customer
        $resolvedAddress = $counterparty?->address ?: ($customer?->address ?: '');
        $resolvedPhone = $counterparty?->phone ?: ($customer?->phone ?: '');
        if ($resolvedAddress || empty($act['customer_address'])) {
            $act['customer_address'] = $resolvedAddress;
        }
        if ($resolvedPhone || empty($act['customer_phone'])) {
            $act['customer_phone'] = $resolvedPhone;
        }

        // Auto-fill tax status from counterparty or customer
        $resolvedTaxGroup = $counterparty?->tax_group ?: ($customer?->tax_group ?: '');
        $resolvedIsSingleTax = $counterparty ? (bool)$counterparty->is_single_tax : ($customer ? (bool)$customer->is_single_tax : true);
        $resolvedIsVatPayer = $counterparty ? (bool)$counterparty->is_vat_payer : ($customer ? (bool)$customer->is_vat_payer : false);
        if ($resolvedTaxGroup || !isset($act['customer_tax_group'])) {
            $act['customer_tax_group'] = $resolvedTaxGroup;
        }
        if (!isset($act['customer_is_single_tax'])) {
            $act['customer_is_single_tax'] = $resolvedIsSingleTax;
        }
        if (!isset($act['customer_is_vat_payer'])) {
            $act['customer_is_vat_payer'] = $resolvedIsVatPayer;
        }

        // Auto-select FOP if customer has a linked FOP and no FOP was manually selected
        if ($customer?->fop_id && empty($act['fop_id'])) {
            $act['fop_id'] = $customer->fop_id;
            $fopId = $customer->fop_id;
        }

        // Auto-fill contract details (priority: counterparty contract -> fallback to FOP contract)
        $resolvedFopId = $act['fop_id'] ?? $fopId;
        $fop = $resolvedFopId ? Fop::find($resolvedFopId) : null;

        $targetContractNumber = $counterparty?->contract_number ?: $fop?->contract_number;
        $targetContractDate = $counterparty?->contract_date ? $counterparty->contract_date->toDateString() : ($fop?->contract_date?->toDateString());

        $partiesChanged = $request->has('act.counterparty_id') || $request->has('act.customer_id') || $request->has('act.fop_id');

        if ($targetContractNumber && (empty($act['contract_number']) || $partiesChanged)) {
            $act['contract_number'] = $targetContractNumber;
        }
        if ($targetContractDate && (empty($act['contract_date']) || $partiesChanged)) {
            $act['contract_date'] = $targetContractDate;
        }

        $repository->set('act', $act);

        return $repository;
    }

    /**
     * @return Layout[]
     */
    protected function layouts(): iterable
    {
        $customerId = null;
        if ($this->query) {
            $customerId = $this->query->get('act.customer_id');
            if (!$customerId) {
                $act = $this->query->get('act');
                if (is_array($act)) {
                    $customerId = $act['customer_id'] ?? null;
                } elseif ($act instanceof Act) {
                    $customerId = $act->customer_id;
                }
            }
        }

        $counterpartyOptions = [];
        if ($customerId) {
            $counterparties = CustomerCounterparty::where('customer_id', $customerId)
                ->active()
                ->get();

            foreach ($counterparties as $cp) {
                $counterpartyOptions[$cp->id] = $cp->full_title;
            }
        } else {
            // Show all active counterparties with customer name
            $allCounterparties = CustomerCounterparty::where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->active()
                ->with('customer')
                ->get();

            foreach ($allCounterparties as $cp) {
                $custName = $cp->customer?->name ? " [{$cp->customer->name}]" : '';
                $counterpartyOptions[$cp->id] = $cp->name . $custName . ($cp->ipn ? " (ІПН: {$cp->ipn})" : '');
            }
        }

        $helpText = 'Оберіть, якщо кошти або документ оформлюються на конкретного ФОПа-платника замовника';
        if ($customerId) {
            if (empty($counterpartyOptions)) {
                $helpText = 'У цього клієнта немає зареєстрованих ФОП контрагентів (документ формується на основного клієнта)';
            } elseif (count($counterpartyOptions) === 1) {
                $helpText = '✓ ФОП контрагента автоматично підтягнуто з профілю замовника';
            }
        }

        return [
            Layout::block(
                Layout::rows([
                    Relation::make('act.fop_id')
                        ->title('Виконавець (мій ФОП)')
                        ->fromModel(Fop::class, 'name')
                        ->applyScope('user')
                        ->required()
                        ->help('Реквізити (ПІБ, ІПН, адреса, IBAN банку) автоматично перенесуться в документ'),

                    Group::make([
                        Relation::make('act.customer_id')
                            ->title('Замовник (Клієнт)')
                            ->fromModel(Customer::class, 'name')
                            ->applyScope('user')
                            ->required(),

                        Select::make('act.counterparty_id')
                            ->title('ФОП контрагент (платник)')
                            ->options($counterpartyOptions)
                            ->empty('Не обрано (документ на основного клієнта)', '')
                            ->help($helpText),
                    ]),

                    Group::make([
                        \Orchid\Screen\Fields\Input::make('act.customer_phone')
                            ->title('Телефон замовника')
                            ->placeholder('+38 (067) 123-45-67')
                            ->help('Відображається в колонці "Від Замовника" в акті'),

                        \Orchid\Screen\Fields\Input::make('act.customer_address')
                            ->title('Адреса замовника')
                            ->placeholder('Україна, 80106, Львівська обл...')
                            ->help('Відображається в колонці "Від Замовника" в акті'),
                    ]),

                    Group::make([
                        Select::make('act.customer_tax_group')
                            ->title('Група єдиного податку замовника')
                            ->options([
                                '' => 'Без групи',
                                '1 група' => '1 група',
                                '2 група' => '2 група',
                                '3 група' => '3 група',
                                '4 група' => '4 група',
                            ])
                            ->empty('Не вказано')
                            ->help('Вкажіть групу ФОП замовника'),

                        \Orchid\Screen\Fields\CheckBox::make('act.customer_is_single_tax')
                            ->title('Платник єдиного податку')
                            ->sendTrueOrFalse()
                            ->value(true)
                            ->placeholder('Платник єдиного податку'),

                        \Orchid\Screen\Fields\CheckBox::make('act.customer_is_vat_payer')
                            ->title('Платник ПДВ')
                            ->sendTrueOrFalse()
                            ->value(false)
                            ->placeholder('Платник ПДВ (якщо не обрано — Не платник ПДВ)'),
                    ]),

                    Group::make([
                        \Orchid\Screen\Fields\Input::make('act.contract_number')
                            ->title('Номер договору')
                            ->placeholder('наприклад: МД18092026-01 (підтягнуто з контрагента або ФОП)'),

                        \Orchid\Screen\Fields\DateTimer::make('act.contract_date')
                            ->title('Дата договору')
                            ->format('Y-m-d')
                            ->allowEmpty()
                            ->help('Автоматично підтягується з обраного контрагента або ФОПа'),
                    ]),
                ])
            )
            ->title('Сторони документа та Договір')
            ->description('Вибір вашого ФОПа та замовника, реквізитів і договору для документа'),
        ];
    }
}
