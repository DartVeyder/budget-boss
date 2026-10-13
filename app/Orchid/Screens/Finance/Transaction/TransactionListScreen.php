<?php

namespace App\Orchid\Screens\Finance\Transaction;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditAuditRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditExpensesRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditIncomeRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditTransferRows;
use App\Orchid\Layouts\Finance\Transaction\TransactionIncomeListener;
use App\Orchid\Layouts\Finance\Transaction\TransactionListLayout;
use App\Orchid\Layouts\Finance\Transaction\TransactionSelection;
use App\Services\Api\Monobank\Monobank;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use App\Services\Finance\Transaction\TransactionService;
use App\Services\Finance\Transaction\TransactionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TransactionListScreen extends Screen
{
   //use TransactionService;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $authUser = Auth::user();
        $sharedIds = $authUser?->getSharedUserIds() ?? [Auth::id()];

        $selectedUserId = request()->get('transaction_user_id') ?: request()->get('user_id');
        $selectedUserId = (!empty($selectedUserId) && is_numeric($selectedUserId)) ? (int)$selectedUserId : null;
        if ($selectedUserId && !in_array($selectedUserId, $sharedIds, true)) {
            $selectedUserId = null;
        }

        $transactionsQuery = FinanceTransaction::with(['attachment', 'customer', 'counterparty', 'bill', 'category', 'currency', 'transactionType', 'user'])
            ->filters(TransactionSelection::class);

        if ($selectedUserId) {
            $transactionsQuery->where('finance_transactions.user_id', $selectedUserId);
        } else {
            $transactionsQuery->whereIn('finance_transactions.user_id', $sharedIds);
        }

        // Calculate separated summary per user for the summary card
        $userSummary = [];
        if ($authUser && $authUser->isSharingBudget()) {
            foreach ($authUser->getSharedUsers() as $su) {
                $suInc = (float)FinanceTransaction::where('user_id', $su->id)
                    ->where('is_balance', 1)
                    ->where('type', 'income')
                    ->sum('currency_amount');
                $suExp = abs((float)FinanceTransaction::where('user_id', $su->id)
                    ->where('is_balance', 1)
                    ->where('type', 'expenses')
                    ->sum('currency_amount'));
                $suCount = FinanceTransaction::where('user_id', $su->id)->count();

                $userSummary[] = [
                    'user' => $su,
                    'is_me' => ($su->id === $authUser->id),
                    'income' => $suInc,
                    'expenses' => $suExp,
                    'net' => $suInc - $suExp,
                    'count' => $suCount,
                    'is_active' => ($selectedUserId === $su->id),
                ];
            }
        }

        return [
            "transactions" => $transactionsQuery
                ->defaultSort('created_at', 'desc')
                ->paginate(),
            "user_summary" => $userSummary,
            "is_sharing_budget" => $authUser?->isSharingBudget() ?? false,
            "selected_user_id" => $selectedUserId,
            "shared_users" => $authUser?->getSharedUsers() ?? collect(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Transactions';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.finance.transactions',
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
            ModalToggle::make(__('Income'))
                ->modal('income')
                ->method('saveIncome'),
            ModalToggle::make(__('Expenses'))
                ->modal('expenses')
                ->method('saveExpenses'),
            ModalToggle::make(__('Transfer'))
                ->modal('transfer')
                ->method('saveTransfer'),
            ModalToggle::make(__('Audit'))
                ->modal('audit')
                ->method('saveAudit'),
            Button::make("Mono")
                ->method("saveMono")
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
            Layout::view('finance.transaction.user-pills'),
            TransactionSelection::class,
            TransactionListLayout::class,
            Layout::modal('income', [
                TransactionIncomeListener::class
            ])->title(__('New income')),
            Layout::modal('expenses', [
                TransactionEditExpensesRows::class
            ])->title(__('New expenses')),
            Layout::modal('transfer', [
                TransactionEditTransferRows::class
            ])->title(__('Transfer')),
            Layout::modal('audit', [
                TransactionEditAuditRows::class
            ])->title(__('Audit')),

        ];
    }



    public function  saveIncome(Request $request, FinanceTransaction $transaction,TransactionIncomeService $transactionIncomeService):void
    {
        $data = $transactionIncomeService->createInsertData($request);
        if (!empty($data['finance_bill_id'])) {
            $bill = FinanceBill::find($data['finance_bill_id']);
            if ($bill && $bill->user_id !== Auth::id() && !Auth::user()->canEditSharedBudgetWith($bill->user_id)) {
                Toast::error(__('У вас немає прав редактора для цього спільного рахунку (тільки перегляд).'));
                return;
            }
        }

        $taxDetails = $data['tax_details'] ?? [];
        unset($data['attachment'], $data['tax_details']);
        
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
        
        $transactionIncomeService->updateStatusInvoice($data['finance_invoice_id'] ?? null);
        Toast::info(__('You have successfully created.'));
    }

    public function  saveExpenses(Request $request, FinanceTransaction $transaction, TransactionExpensesService $transactionExpensesService ):void
    {
        $data = $transactionExpensesService->createInsertData($request);
        if (!empty($data['finance_bill_id'])) {
            $bill = FinanceBill::find($data['finance_bill_id']);
            if ($bill && $bill->user_id !== Auth::id() && !Auth::user()->canEditSharedBudgetWith($bill->user_id)) {
                Toast::error(__('У вас немає прав редактора для цього спільного рахунку (тільки перегляд).'));
                return;
            }
        }

        unset($data['attachment']);
        $transaction->fill($data)->save($data);
        $transaction->attachment()->syncWithoutDetaching(
            $request->input('transaction.attachment', [])
        );

        Toast::info(__('You have successfully created.'));
    }

    public function  saveTransfer(Request $request,  TransactionsService  $transactionsService): void{
        $transaction = $request->input('transaction', []);
        $attachments = $transaction['attachment'] ?? [];
        unset($transaction['attachment']);
        $transaction['is_balance'] = 0;
        $bills =  $request->input('bills', []);

        if (empty($bills['with_bill_id']) || empty($bills['to_bill_id'])) {
            Toast::error(__('Будь ласка, виберіть обидва рахунки для переказу'));
            return;
        }

        $sharedIds = Auth::user()?->getSharedUserIds() ?? [Auth::id()];
        $withBill = FinanceBill::whereIn('user_id', $sharedIds)->find($bills['with_bill_id']);
        $toBill = FinanceBill::whereIn('user_id', $sharedIds)->find($bills['to_bill_id']);

        if (!$withBill || !$toBill) {
            Toast::error(__('Рахунок не знайдено'));
            return;
        }

        if ($withBill->user_id !== Auth::id() && !Auth::user()->canEditSharedBudgetWith($withBill->user_id)) {
            Toast::error(__('У вас немає прав редактора для списання з рахунку партнера.'));
            return;
        }

        if ($toBill->user_id !== Auth::id() && !Auth::user()->canEditSharedBudgetWith($toBill->user_id)) {
            Toast::error(__('У вас немає прав редактора для зарахування на рахунок партнера.'));
            return;
        }

        if ($withBill->finance_currency_id != $toBill->finance_currency_id) {
            Toast::info(__('Transfers between accounts with different currencies are prohibited'));
            return ;
        }

        if ($bills['with_bill_id'] == $bills['to_bill_id']) {
            Toast::info(__('It is not possible to transfer to the same account'));
            return ;
        }

        $amount = (float)($transaction['amount'] ?? 0);

        $income =  $transaction;
        $income['type'] = 'income';
        $income['finance_bill_id']  =  $bills['to_bill_id'];
        $income = array_merge($income, $transactionsService->getCurrency((int)$bills['to_bill_id'], $amount));

        $expenses =  $transaction;
        $expenses['type'] = 'expenses';
        $expenses['finance_bill_id']  = $bills['with_bill_id'];
        $expenses = array_merge($expenses, $transactionsService->getCurrency((int)$bills['with_bill_id'], $amount));
        $expenses['amount'] = $transactionsService->getAmountNegative($expenses['amount']);
        $expenses['currency_amount'] = $transactionsService->getAmountNegative($expenses['currency_amount']);

        $exp = Auth::user()->transactions()->create($expenses);
        $inc = Auth::user()->transactions()->create($income);

        if (!empty($attachments)) {
            $exp->attachment()->syncWithoutDetaching($attachments);
            $inc->attachment()->syncWithoutDetaching($attachments);
        }

        Toast::info(__('You have successfully created.'));
    }

    public function  saveAudit(Request $request,  TransactionsService  $transactionsService){

        $data = [];
        $transaction = $request->input('transaction');
        $attachments = $transaction['attachment'] ?? [];
        unset($transaction['attachment']);
        $total = FinanceTransaction::where('finance_bill_id', $transaction['bill_id'])->sum('amount');

        $diffTotal = $transaction['current_balance'] - $total;
        $data['amount'] =  $diffTotal ;
        $data['type'] =  ( $diffTotal > 0) ? 'income': 'expenses';

        $data['transaction_type_id'] = 4;
        $data['transaction_category_id'] =  2;
        if($transaction['created_at']){
            $data['accrual_date'] = $transaction['created_at'];
            $data['created_at'] = $transaction['created_at'];
        }
        $data['finance_bill_id'] = $transaction['bill_id'];
        $data = array_merge(  $data,$transactionsService->getCurrency( $transaction['bill_id'],  $diffTotal));

        $auditTransaction = Auth::user()->transactions()->create($data);
        if (!empty($attachments)) {
            $auditTransaction->attachment()->syncWithoutDetaching($attachments);
        }
        Toast::info(__('You have successfully created.'));
    }
    public function remove(Request $request,TransactionsService  $transactionsService): object
    {
        $transaction = FinanceTransaction::findOrFail($request->get('id'));
        if ($transaction->user_id !== Auth::id() && !Auth::user()->canEditSharedBudgetWith($transaction->user_id)) {
            Toast::error(__('У вас немає прав для видалення транзакцій іншого учасника бюджету.'));
            return redirect()->back();
        }

        $transaction->delete();

        if ($transaction->mono_id) {
            $pairMonoId = str_ends_with($transaction->mono_id, '_fop')
                ? substr($transaction->mono_id, 0, -4)
                : $transaction->mono_id . '_fop';
            FinanceTransaction::where('user_id', Auth::id())
                ->where('mono_id', $pairMonoId)
                ->delete();
        }

        if($transaction->finance_invoice_id){
            $transactionsService->updateStatusInvoice($transaction->finance_invoice_id);
        }

        Toast::info(__('You have successfully remove'));
        return redirect()->back();
    }

    public function saveMono( TransactionExpensesService $transactionExpensesService )
    {
        $setting = Auth::user()->setting;
        if (!$setting || !$setting->monobank_active || empty($setting->monobank_api_key)) {
            Toast::warning(__('Please configure your Monobank API key in settings first.'));
            return;
        }

        $token = $setting->monobank_api_key;

        $latestTransaction = FinanceTransaction::where('user_id', Auth::id())
            ->whereNotNull('mono_id')
            ->orderBy('created_at', 'DESC')
            ->first();

        if (is_null($latestTransaction)) {
            $from = Carbon::now()->startOfMonth();
        } else {
            // Check back up to 30 days (or last transaction minus 7 days) to catch any delayed/skipped refunds
            $from = Carbon::parse($latestTransaction->created_at)->subDays(7);
            $maxLookback = Carbon::now()->subDays(30);
            if ($from->lt($maxLookback)) {
                $from = $maxLookback;
            }
        }

        $to = Carbon::now();

        $statement = Monobank::getStatement($from, $to, $token);
        if ($statement) {
            $data = $transactionExpensesService->createInsertDataMono($statement);
            if (!empty($data) && FinanceTransaction::insert($data)) {
                Toast::info(__('Успішно імпортовано ' . count($data) . ' транзакцій з Моно'));
            } else {
                Toast::info(__('Нема нових транзакцій з Моно'));
            }
        } else {
            Toast::info(__('Нема нових транзакцій з Моно'));
        }
    }

    public function asyncGetCustomerDefaults($customerData = null): iterable
    {
        $customerId = is_array($customerData) ? ($customerData['customer_id'] ?? null) : $customerData;

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
