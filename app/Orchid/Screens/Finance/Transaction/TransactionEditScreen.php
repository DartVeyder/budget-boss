<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditAuditRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditExpensesRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditIncomeRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditTransferRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionIncomeListener;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use App\Services\Finance\Transaction\TransactionService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class TransactionEditScreen extends Screen

{
    use TransactionService;

    public $transaction;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinanceTransaction $transaction): iterable
    {
        if ($transaction->exists && $transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $transaction->load(['attachment', 'taxes', 'customer.fop.fopGroup.taxRates', 'customer.counterparties', 'counterparty']);

        $taxRates = $transaction->taxes->pluck('id')->toArray();
        if (empty($taxRates) && $transaction->customer?->fop?->fopGroup?->taxRates) {
            $taxRates = $transaction->customer->fop->fopGroup->taxRates->pluck('id')->toArray();
        }

        $taxStatus = $transaction->customer?->fop?->tax_status ?? 'without_taxes';

        return [
            'transaction' => $transaction,
            'tax_rates' => $taxRates,
            'tax_status' => $taxStatus,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->transaction->exists ?  'Transactions edit' : 'Transactions add';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Сформувати Акт')
                ->icon('bs.file-earmark-plus')
                ->route('platform.acts.create', ['transaction_id' => $this->transaction->id])
                ->canSee($this->transaction->exists && $this->transaction->type === 'income'),

            Button::make(__('Back'))
                ->method('back'),
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method($this->getMethod($this->transaction->transaction_type_id)),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return  $this->getLayout($this->transaction->transaction_type_id);

    }

    private  function  getMethod(int $typeId) :string
    {
        switch ($typeId) {
            case 1:
                return 'saveExpenses';
            case 2:
                return 'saveIncome';
            case 3:
                return 'saveTransfer';
            case 4:
                return 'saveAudit';
            default:
                return '';
        }
    }
    private  function  getLayout(int $typeId) :iterable
    {
        $layouts = [];

        switch ($typeId) {
            case 1:
                $layouts[] = TransactionEditExpensesRows::class;
                break;
            case 2:
                $layouts[] = TransactionIncomeListener::class;
                break;
            case 3:
                $layouts[] = TransactionEditTransferRows::class;
                break;
            case 4:
                $layouts[] = TransactionEditAuditRows::class;
                break;
        }
         if ($this->transaction->exists && $this->transaction->attachment->count() > 0) {
            $layouts[] = \Orchid\Support\Facades\Layout::view('finance.transaction.partials.attachments');
        }

        return $layouts;
    }

    public function  saveIncome(Request $request, FinanceTransaction $transaction):void
    {
        $transactionIncome = new TransactionIncomeService();
        $data = $transactionIncome->createInsertData($request);
        $taxDetails = $data['tax_details'] ?? [];
        unset( $data['balance'],$data['balance_bill'],$data['attachment'], $data['tax_details']);
        
        $transaction->fill($data);
        unset($transaction->tax_details);
        $transaction->save();
        $transaction->attachment()->syncWithoutDetaching(
            $request->input('transaction.attachment', [])
        );

        if (!empty($taxDetails)) {
            $transaction->taxes()->sync($taxDetails);
        } else {
            $transaction->taxes()->detach();
        }

        $transactionIncome->updateStatusInvoice($data['finance_invoice_id'] ?? null);
        Toast::info(__('You have successfully created.'));
    }

    public function  saveExpenses(Request $request, FinanceTransaction $transaction):void
    {
        $transactionExpenses = new TransactionExpensesService();
        $data = $transactionExpenses->createInsertData($request);
        unset( $data['balance'],$data['balance_bill'],$data['attachment']);
        $transaction->fill($data)->save($data);
        $transaction->attachment()->syncWithoutDetaching(
            $request->input('transaction.attachment', [])
        );

        Toast::info(__('You have successfully created.'));
    }

    public function saveTransfer(Request $request, FinanceTransaction $transaction): void
    {
        $data = $request->input('transaction', []);
        $attachments = $request->input('transaction.attachment', []);
        unset($data['attachment']);

        $transaction->fill($data)->save();

        if (!empty($attachments)) {
            $transaction->attachment()->syncWithoutDetaching($attachments);
        }

        Toast::info(__('You have successfully created.'));
    }

    public function saveAudit(Request $request, FinanceTransaction $transaction): void
    {
        $data = $request->input('transaction', []);
        $attachments = $request->input('transaction.attachment', []);
        unset($data['attachment']);

        $transaction->fill($data)->save();

        if (!empty($attachments)) {
            $transaction->attachment()->syncWithoutDetaching($attachments);
        }

        Toast::info(__('You have successfully created.'));
    }

    public function  back(){
        return redirect()->route('platform.transactions');
    }

    public function asyncGetCustomerDefaults($customerData = null)
    {
        // Orchid can pass nested data as an array if the target has dots
        $customerId = is_array($customerData) ? ($customerData['customer_id'] ?? null) : $customerData;
        
        \Illuminate\Support\Facades\Log::info('asyncGetCustomerDefaults called', ['customerId' => $customerId]);

        $customer = \App\Models\Customer::with(['fop.fopGroup.taxRates', 'counterparties' => fn ($q) => $q->active()])->find($customerId);

        $counterpartyId = null;
        if ($customer && $customer->counterparties->count() === 1) {
            $counterpartyId = $customer->counterparties->first()->id;
        }

        return [
            'transaction' => [
                'customer_id' => $customerId,
                'counterparty_id' => $counterpartyId,
                'finance_bill_id' => $customer?->fop?->finance_bill_id,
                'transaction_category_id' => $customer?->fop?->transaction_category_id,
            ],
            'tax_status' => $customer?->fop?->tax_status,
            'tax_rates' => $customer?->fop?->fopGroup?->taxRates?->pluck('id')->toArray(),
        ];
    }

}
