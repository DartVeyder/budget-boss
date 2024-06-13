<?php
namespace App\Orchid\Layouts\Finance\Invoice;


use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
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
                    fn(FinanceInvoice $invoice) => $invoice->translated_status
                ),
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
                ->width('100px')
                ->render(fn (FinanceInvoice $invoice) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
//                        Link::make(__('Edit'))
//                            ->route('platform.transactions.edit', [$invoice->id])
//
//                            ->icon('bs.pencil'),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->method('remove', [
                                'id' => $invoice->id,
                            ]),
                    ])),
        ];
    }
}
