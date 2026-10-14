<?php

namespace App\Orchid\Screens\Finance\Invoice;

use App\Models\FinanceInvoice;
use App\Orchid\Layouts\Customer\CustomerSaveRows;
use App\Orchid\Layouts\Finance\Invoice\InvoiceListLayout;
use App\Orchid\Layouts\Finance\Invoice\InvoiceSaveRows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class InvoiceListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            "invoices" =>
                FinanceInvoice::with(['customer', 'fop', 'counterparty', 'currency', 'attachment', 'act'])
                    ->filters()
                    ->where('user_id', Auth::user()->id)
                    ->defaultSort('id', 'desc')
                    ->paginate()
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Рахунки на оплату';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.finance.invoices',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Створити рахунок')
                ->icon('bs.plus-circle')
                ->modal('createInvoice')
                ->method('save'),

            ModalToggle::make('Створити клієнта')
                ->icon('bs.person-plus')
                ->modal('createCustomer')
                ->method('saveCustomer'),
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
            InvoiceListLayout::class,
            Layout::modal('createInvoice', [
                InvoiceSaveRows::class
            ])->applyButton('Зберегти')->title('Новий рахунок на оплату'),
            Layout::modal('createCustomer', [
                CustomerSaveRows::class
            ])->applyButton('Зберегти')->title('Новий клієнт'),

            Layout::modal('asyncUploadInvoiceModal', Layout::rows([
                Input::make('invoice.id')->type('hidden'),

                Upload::make('invoice.attachment')
                    ->title('Підписаний рахунок (скан / PDF)')
                    ->acceptedFiles('.pdf,.docx,.doc,.jpg,.jpeg,.png')
                    ->help('Завантажте підписану скан-копію або PDF документ'),

                Select::make('invoice.status')
                    ->title('Статус рахунку')
                    ->options([
                        'not_paid' => 'Не оплачено',
                        'part paid' => 'Оплачено частково',
                        'paid' => 'Оплачено',
                        'cancelled' => 'Скасовано',
                    ])
                    ->help('Статус оплати за цим рахунком'),
            ]))
            ->async('asyncGetInvoice')
            ->applyButton('Зберегти')
            ->title('Підписаний рахунок'),
        ];
    }

    /**
     * Async get invoice data for upload modal.
     */
    public function asyncGetInvoice(Request $request): iterable
    {
        $invoiceId = $request->input('invoice');
        $invoice = FinanceInvoice::where('user_id', Auth::id())->with('attachment')->findOrFail($invoiceId);

        return [
            'invoice' => $invoice,
        ];
    }

    /**
     * Save signed invoice attachment and status.
     */
    public function saveSignedInvoice(Request $request)
    {
        $invoiceId = $request->input('invoice.id');
        $invoice = FinanceInvoice::where('user_id', Auth::id())->findOrFail($invoiceId);

        $attachments = $request->input('invoice.attachment', []);
        $invoice->attachment()->sync($attachments);

        if ($request->has('invoice.status')) {
            $invoice->status = $request->input('invoice.status');
        }
        $invoice->save();

        Toast::info('Підписаний рахунок успішно збережено.');

        return redirect()->route('platform.invoices');
    }

    public function saveCustomer(Request $request)
    {
        Auth::user()->customers()->create($request->all());
        Toast::info('Клієнта успішно створено.');
    }

    public function save(Request $request, FinanceInvoice $financeInvoice)
    {
        $invoice = $request->input('invoice', []);
        $attachments = $request->input('invoice.attachment', []);
        unset($invoice['attachment']);

        $invoice['user_id'] = Auth::id();
        if (!$financeInvoice->exists && empty($invoice['invoice_number'])) {
            $invoice['invoice_number'] = app(\App\Services\Finance\Act\DocumentGenerationService::class)->generateInvoiceNumber();
        }
        $financeInvoice->fill($invoice)->save();

        if (!empty($attachments)) {
            $financeInvoice->attachment()->sync($attachments);
        }

        // If this invoice has a linked act, sync changes to the act as well
        if ($financeInvoice->act) {
            $act = $financeInvoice->act;
            if (isset($invoice['total'])) {
                $act->total_amount = (float)$invoice['total'];
            }
            if (!empty($invoice['customer_id'])) {
                $act->customer_id = $invoice['customer_id'];
            }
            if (!empty($invoice['counterparty_id'])) {
                $act->counterparty_id = $invoice['counterparty_id'];
            }
            if (!empty($invoice['fop_id'])) {
                $act->fop_id = $invoice['fop_id'];
            }
            if (!empty($invoice['invoice_date'])) {
                $act->act_date = $invoice['invoice_date'];
            }
            if (!empty($invoice['contract_number'])) {
                $act->contract_number = $invoice['contract_number'];
            }
            if (!empty($invoice['contract_date'])) {
                $act->contract_date = $invoice['contract_date'];
            }
            $act->saveQuietly();
        } else {
            // Automatically generate and link an Act for new invoice
            $documentService = app(\App\Services\Finance\Act\DocumentGenerationService::class);
            $prepared = $documentService->prepareFromInvoice($financeInvoice);
            $newAct = \App\Models\Act::create($prepared);
            $items = $prepared['items'] ?? [];
            foreach ($items as $item) {
                $newAct->items()->create($item);
            }
            $financeInvoice->refresh();
        }

        Toast::info('Рахунок успішно збережено.');
    }

    public function generateInvoiceNumber(): string
    {
        return app(\App\Services\Finance\Act\DocumentGenerationService::class)->generateInvoiceNumber();
    }

    public function remove(Request $request): object
    {
        $invoice = FinanceInvoice::where('user_id', Auth::id())->findOrFail($request->get('id'));
        $invoice->delete();

        Toast::info('Рахунок успішно видалено.');
        return redirect()->route('platform.invoices');
    }
}

