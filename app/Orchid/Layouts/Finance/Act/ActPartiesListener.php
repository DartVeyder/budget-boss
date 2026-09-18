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
        'act.customer_id',
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
        $customerId = $request->input('act.customer_id');
        $customer = $customerId ? Customer::with(['fop', 'counterparties' => fn ($q) => $q->active()])->find($customerId) : null;

        $act = $repository->get('act', []);
        if ($act instanceof Act) {
            $act = $act->toArray();
        }

        $act['customer_id'] = $customerId;

        // Auto-select counterparty if customer has exactly 1 counterparty
        if ($customer && $customer->counterparties->count() === 1) {
            $act['counterparty_id'] = $customer->counterparties->first()->id;
        } else {
            $currentCpId = $act['counterparty_id'] ?? null;
            if ($currentCpId && $customer && !$customer->counterparties->pluck('id')->contains($currentCpId)) {
                $act['counterparty_id'] = null;
            }
        }

        // Auto-select FOP if customer has a linked FOP and no FOP was manually selected
        if ($customer?->fop_id && empty($act['fop_id'])) {
            $act['fop_id'] = $customer->fop_id;
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
                ])
            )
            ->title('Сторони документа')
            ->description('Вибір вашого ФОПа та замовника для автоматичного заповнення реквізитів'),
        ];
    }
}
