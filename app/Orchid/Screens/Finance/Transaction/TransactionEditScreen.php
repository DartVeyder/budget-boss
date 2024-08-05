<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditAuditRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditExpensesRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditIncomeRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditTransferRows;
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
//            Button::make(__('Save'))
//                ->icon('bs.check-circle')
//                ->method($this->getMethod($this->transaction->transaction_type_id)),
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
        switch ($typeId) {
            case 1:
                return [ TransactionEditExpensesRows::class ];
            case 2:
                return [ TransactionEditIncomeRows::class ];
            case 3:
                return [ TransactionEditTransferRows::class ];
            case 4:
                return [ TransactionEditAuditRows::class ];
            default:
                return [];
        }
    }

 /*   public function save(Request $request, FinanceTransaction $transaction){

        $transaction->fill($request->input('transaction'))->save();
        Toast::info(__('You have successfully created.'));
        return redirect()->route('platform.transactions');

    }*/

}
