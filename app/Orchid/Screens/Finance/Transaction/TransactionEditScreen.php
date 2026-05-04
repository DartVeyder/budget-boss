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
        $transaction->load('attachment');
        return [
            'transaction' => $transaction
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
                $layouts[] = TransactionEditIncomeRows::class;
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
        unset( $data['balance'],$data['balance_bill'],$data['attachment']);
        $transaction->fill($data)->save();
        $transaction->attachment()->syncWithoutDetaching(
            $request->input('transaction.attachment', [])
        );
        $transactionIncome->updateStatusInvoice($data['finance_invoice_id']);
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

    public function  back(){
        return redirect()->route('platform.transactions');
    }

    public function asyncGetCustomerDefaults($customerData = null)
    {
        // Orchid can pass nested data as an array if the target has dots
        $customerId = is_array($customerData) ? ($customerData['customer_id'] ?? null) : $customerData;
        
        \Illuminate\Support\Facades\Log::info('asyncGetCustomerDefaults called', ['customerId' => $customerId]);

        $customer = \App\Models\Customer::find($customerId);

        return [
            'transaction' => [
                'customer_id' => $customerId,
                'finance_bill_id' => $customer?->finance_bill_id,
            ],
            'tax_status' => $customer?->tax_status,
            'tax_rates' => $customer?->tax_rate_id,
        ];
    }

}
