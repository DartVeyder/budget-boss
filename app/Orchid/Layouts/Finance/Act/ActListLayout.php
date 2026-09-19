<?php

namespace App\Orchid\Layouts\Finance\Act;

use App\Models\Act;
use App\Models\Customer;
use App\Models\Fop;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ActListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    public $target = 'acts';

    /**
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('act_number', '№ Акту')
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Act $act) =>
                    Link::make($act->act_number)
                        ->route('platform.acts.edit', $act)
                        ->icon('bs.file-earmark-text')
                ),

            TD::make('act_date', 'Дата акту')
                ->sort()
                ->filter(TD::FILTER_DATE_RANGE)
                ->render(fn (Act $act) => $act->act_date ? $act->act_date->format('d.m.Y') : '—'),

            TD::make('customer_id', 'Замовник / Платник')
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    Customer::where('user_id', Auth::id())->pluck('name', 'id')
                )
                ->render(function (Act $act) {
                    $name = $act->customer ? e($act->customer->name) : '—';
                    if ($act->counterparty) {
                        $name .= "<br><span class='text-muted small'><i class='bi bi-person-badge'></i> " . e($act->counterparty->name) . "</span>";
                    }
                    return $name;
                }),

            TD::make('fop_id', 'Виконавець (ФОП)')
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    Fop::where('user_id', Auth::id())->pluck('name', 'id')
                )
                ->render(fn (Act $act) => $act->fop?->name ?? '—'),

            TD::make('total_amount', 'Сума')
                ->sort()
                ->filter(TD::FILTER_NUMBER_RANGE)
                ->render(fn (Act $act) => '<strong>' . number_format($act->total_amount, 2, '.', ' ') . ' грн</strong>'),

            TD::make('status', 'Статус')
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    [
                        'draft' => 'Чернетка',
                        'sent' => 'Надіслано',
                        'signed' => 'Підписано',
                        'cancelled' => 'Скасовано',
                    ]
                )
                ->render(fn (Act $act) => $act->status_badge),

            TD::make('signed_doc', 'Підписаний акт')
                ->render(function (Act $act) {
                    $attachments = $act->attachment;
                    $fromTransaction = false;

                    if (($attachments === null || $attachments->isEmpty()) && $act->transaction && $act->transaction->attachment->isNotEmpty()) {
                        $txActFiles = $act->transaction->attachment->filter(function ($att) {
                            $n = mb_strtolower($att->original_name ?? '');
                            return str_contains($n, 'акт') || str_contains($n, 'akt');
                        });
                        $attachments = $txActFiles->isNotEmpty() ? $txActFiles : $act->transaction->attachment;
                        $fromTransaction = true;
                    }

                    if ($attachments && $attachments->isNotEmpty()) {
                        $html = [];
                        foreach ($attachments as $att) {
                            $url = $att->url();
                            $name = e(\Illuminate\Support\Str::limit($att->original_name, 22));
                            $badgeStyle = $fromTransaction 
                                ? 'badge bg-info-subtle text-info border border-info-subtle' 
                                : 'badge bg-success-subtle text-success border border-success-subtle';
                            $icon = $fromTransaction ? 'bi-paperclip' : 'bi-file-earmark-check';
                            $prefix = $fromTransaction ? '<span class="badge bg-secondary-subtle text-dark border me-1 small">з транзакції</span>' : '';

                            $html[] = "<div class='d-flex align-items-center mb-1'>{$prefix}<a href='{$url}' target='_blank' class='{$badgeStyle} text-decoration-none d-inline-flex align-items-center' title='{$att->original_name}'><i class='bi {$icon} me-1'></i>{$name}</a></div>";
                        }
                        return implode('', $html);
                    }

                    return ModalToggle::make('Додати скан')
                        ->icon('bs.upload')
                        ->modal('asyncUploadActModal')
                        ->modalTitle('Завантажити підписаний акт № ' . $act->act_number)
                        ->method('saveSignedAct')
                        ->asyncParameters(['act' => $act->id])
                        ->class('btn btn-sm btn-outline-secondary');
                }),

            TD::make('actions', 'Дії')
                ->align(TD::ALIGN_CENTER)
                ->width('120px')
                ->render(fn (Act $act) =>
                    DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            Link::make('Друк / Перегляд (A4)')
                                ->icon('bs.printer')
                                ->route('platform.acts.print', $act)
                                ->target('_blank'),

                            ModalToggle::make('Підписаний акт (скан)')
                                ->icon('bs.file-earmark-arrow-up')
                                ->modal('asyncUploadActModal')
                                ->modalTitle('Підписаний акт: № ' . $act->act_number)
                                ->method('saveSignedAct')
                                ->asyncParameters(['act' => $act->id]),

                            Link::make('Редагувати')
                                ->icon('bs.pencil')
                                ->route('platform.acts.edit', $act),

                            Button::make('Видалити')
                                ->icon('bs.trash3')
                                ->confirm('Ви впевнені, що хочете видалити цей акт?')
                                ->method('remove', [
                                    'id' => $act->id,
                                ]),
                        ])
                ),
        ];
    }
}
