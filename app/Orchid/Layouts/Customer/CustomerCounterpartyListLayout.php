<?php

namespace App\Orchid\Layouts\Customer;

use App\Models\CustomerCounterparty;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class CustomerCounterpartyListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    public $target = 'counterparties';

    /**
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('name', 'ПІБ / Назва ФОПа')
                ->render(fn (CustomerCounterparty $counterparty) =>
                    ModalToggle::make($counterparty->name)
                        ->icon('bs.pencil')
                        ->modal('asyncEditCounterpartyModal')
                        ->modalTitle('Редагування ФОП контрагента: ' . $counterparty->name)
                        ->method('saveCounterparty')
                        ->asyncParameters([
                            'counterparty' => $counterparty->id,
                        ])
                ),

            TD::make('ipn', 'ІПН / РНОКПП')
                ->render(fn (CustomerCounterparty $counterparty) => $counterparty->ipn ?: '—'),

            TD::make('iban', 'IBAN')
                ->render(fn (CustomerCounterparty $counterparty) => $counterparty->iban ?: '—'),

            TD::make('bank_name', 'Банк')
                ->render(fn (CustomerCounterparty $counterparty) => $counterparty->bank_name ?: '—'),

            TD::make('contract', 'Договір')
                ->render(function (CustomerCounterparty $counterparty) {
                    if (!$counterparty->contract_number && !$counterparty->contract_date) {
                        return '<span class="text-muted">—</span>';
                    }
                    $lines = [];
                    if ($counterparty->contract_number) {
                        $lines[] = '№ ' . e($counterparty->contract_number);
                    }
                    if ($counterparty->contract_date) {
                        $lines[] = '<small class="text-muted">від ' . $counterparty->contract_date->format('d.m.Y') . '</small>';
                    }
                    return implode('<br>', $lines);
                }),

            TD::make('notes', 'Примітки')
                ->render(fn (CustomerCounterparty $counterparty) => $counterparty->notes ?: '—'),

            TD::make('is_active', 'Статус')
                ->render(fn (CustomerCounterparty $counterparty) =>
                    $counterparty->is_active
                        ? '<span class="badge bg-success">Активний</span>'
                        : '<span class="badge bg-secondary">Неактивний</span>'
                ),

            TD::make('actions', 'Дії')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (CustomerCounterparty $counterparty) =>
                    DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            ModalToggle::make('Редагувати')
                                ->icon('bs.pencil')
                                ->modal('asyncEditCounterpartyModal')
                                ->modalTitle('Редагування ФОП контрагента: ' . $counterparty->name)
                                ->method('saveCounterparty')
                                ->asyncParameters([
                                    'counterparty' => $counterparty->id,
                                ]),

                            Button::make('Видалити')
                                ->icon('bs.trash3')
                                ->confirm('Ви впевнені, що хочете видалити цього контрагента?')
                                ->method('deleteCounterparty', [
                                    'id' => $counterparty->id,
                                ]),
                        ])
                ),
        ];
    }
}
