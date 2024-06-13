<?php

namespace App\Orchid\Screens\Finance\Invoice;

use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
use App\Orchid\Layouts\Finance\Invoice\InvoiceListLayout;
use App\Orchid\Layouts\Finance\Invoice\InvoiceSaveRows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
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
                FinanceInvoice::filters()
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
        return 'Invoices';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Add'))
                ->modal('createInvoice')
                ->method('save'),
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
            ])->applyButton(__('Save'))->title(__('New invoice')),
        ];
    }

    public function save(Request $request, FinanceInvoice $financeInvoice)
    {
        $invoice = $request->input('invoice');
        $invoice['invoice_number'] = $this->generateInvoiceNumber();
        $financeInvoice->fill($invoice)->save();
        Toast::info(__('You have successfully created.'));
    }


    public function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $latestInvoice = FinanceInvoice::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = $latestInvoice ? (int)substr($latestInvoice->invoice_number, -4) : 0;
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return 'INV-' . $date . '-' . $nextNumber;
    }

    public function remove(Request $request): object
    {
        FinanceInvoice::findOrFail($request->get('id'))->delete();

        Toast::info(__('You have successfully remove'));
        return redirect()->route('platform.invoices');
    }
}
