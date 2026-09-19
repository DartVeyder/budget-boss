<?php
namespace App\Orchid\Layouts\Finance\Invoice;


use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class InvoiceListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'invoices';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID')),
            TD::make('invoice_number', __('Invoice number')),
            TD::make('customer_id', __('From whom'))
                ->render(
                    fn(FinanceInvoice $invoice) => $invoice->customer->name
                ),
            TD::make('amount_paid', __('Amount paid')) ->render(
                fn(FinanceInvoice $invoice) => $invoice->amount_paid ." ".$invoice->currency->symbol
            ),
            TD::make('total', __('Total')) ->render(
                fn(FinanceInvoice $invoice) => $invoice->total ." ". $invoice->currency->symbol
            ),
            TD::make('status', __('Status'))
                ->render(
                    fn(FinanceInvoice $invoice) => $invoice->status_badge
                ),
            TD::make('signed_doc', 'Підписаний рахунок')
                ->render(function (FinanceInvoice $invoice) {
                    $attachments = $invoice->attachment;
                    $fromTransaction = false;

                    if (($attachments === null || $attachments->isEmpty())) {
                        $tx = $invoice->transactions?->first() ?: $invoice->act?->transaction;
                        if ($tx && $tx->attachment && $tx->attachment->isNotEmpty()) {
                            $txInvFiles = $tx->attachment->filter(function ($att) {
                                $n = mb_strtolower($att->original_name ?? '');
                                return str_contains($n, 'рах') || str_contains($n, 'rakh') || str_contains($n, 'inv');
                            });
                            $attachments = $txInvFiles->isNotEmpty() ? $txInvFiles : $tx->attachment;
                            $fromTransaction = true;
                        }
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
                        ->modal('asyncUploadInvoiceModal')
                        ->modalTitle('Завантажити підписаний рахунок ' . $invoice->invoice_number)
                        ->method('saveSignedInvoice')
                        ->asyncParameters(['invoice' => $invoice->id])
                        ->class('btn btn-sm btn-outline-secondary');
                }),
            TD::make('created_at', __('Created'))
                ->sort()
                ->filter(TD::FILTER_DATE_RANGE)
                ->render(
                    fn (FinanceInvoice $invoice) => $invoice->created_at
                )
                ->align(TD::ALIGN_RIGHT)
                ->sort(),
            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('120px')
                ->render(fn (FinanceInvoice $invoice) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('Друк Рахунку (A4)')
                            ->icon('bs.printer')
                            ->route('platform.invoices.print', $invoice)
                            ->target('_blank'),

                        ModalToggle::make('Підписаний рахунок (скан)')
                            ->icon('bs.file-earmark-arrow-up')
                            ->modal('asyncUploadInvoiceModal')
                            ->modalTitle('Підписаний рахунок: ' . $invoice->invoice_number)
                            ->method('saveSignedInvoice')
                            ->asyncParameters(['invoice' => $invoice->id]),

                        Link::make('Сформувати Акт')
                            ->icon('bs.file-earmark-plus')
                            ->route('platform.acts.create', ['invoice_id' => $invoice->id]),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm('Видалити цей рахунок?')
                            ->method('remove', [
                                'id' => $invoice->id,
                            ]),
                    ])),
        ];
    }
}
