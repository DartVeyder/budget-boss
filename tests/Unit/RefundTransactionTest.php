<?php

namespace Tests\Unit;

use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionSource;
use App\Models\User;
use App\Models\UserSetting;
use App\Orchid\Screens\DashboardScreen;
use App\Services\Currency\Currency;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RefundTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cancellation_transaction_is_not_counted_as_income(): void
    {
        $user = User::create([
            'name' => 'Refund User',
            'email' => 'refund_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($user);
        UserSetting::create(['user_id' => $user->id, 'currency' => 'UAH']);

        $bill = FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Моно Картка',
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        // 1. Regular Income (Salary: 10 000 UAH)
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 10000.00,
            'currency_amount' => 10000.00,
            'absolute_currency_amount' => 10000.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 2, // Дохід
            'is_balance' => 1,
            'comment' => 'Зарплата',
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        // 2. Cancellation / Refund (Bolt ride cancelled: 109 UAH)
        $refundTx = FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 109.00,
            'currency_amount' => 109.00,
            'absolute_currency_amount' => 109.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 5, // Скасування
            'source_name' => 'скасування. bolt',
            'is_balance' => 1,
            'comment' => 'Повернення коштів',
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        // Verify total bill balance contains BOTH (10 000 + 109 = 10 109)
        $this->assertEquals(10109.00, $bill->calculateTotalBalance());

        // Verify TransactionIncomeService DOES NOT include the 109 cancellation (only 10 000)
        $incomeService = new TransactionIncomeService();
        $incomeService->setTargetUserId($user->id);
        $incomeSum = (float)$incomeService->getSum('currency_amount', false, null, now()->startOfMonth(), now()->endOfMonth());
        $this->assertEquals(10000.00, $incomeSum);

        // Verify DashboardScreen metrics DOES NOT count 109 as income
        $dashboardScreen = new DashboardScreen();
        $dashboardData = $dashboardScreen->query(new FinanceTransaction(), new Request());
        $this->assertEquals(
            Currency::convertValueToCurrency(10000.00),
            $dashboardData['metrics']['currentMonth']['income']
        );
    }

    public function test_monobank_sync_marks_refund_with_type_5(): void
    {
        $user = User::create([
            'name' => 'Mono Sync User',
            'email' => 'monosync_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($user);
        UserSetting::create(['user_id' => $user->id, 'currency' => 'UAH']);

        $bill = FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Моно Картка',
            'bank_name' => 'Монобанк',
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        $service = new TransactionExpensesService();
        $service->setUserId($user->id);

        $fakeMonoData = [
            [
                'id' => 'fake_refund_' . uniqid(),
                'time' => now()->timestamp,
                'description' => 'скасування. bolt',
                'mcc' => 4121,
                'originalMcc' => 4121,
                'amount' => 10900, // +109.00 UAH
                'operationAmount' => 10900,
                'currencyCode' => 980,
                'commissionRate' => 0,
                'cashbackAmount' => 0,
                'balance' => 500000,
                'hold' => false,
            ],
            [
                'id' => 'fake_income_' . uniqid(),
                'time' => now()->timestamp,
                'description' => 'Поповнення картки',
                'mcc' => 4829, // P2P non-merchant
                'originalMcc' => 4829,
                'amount' => 50000, // +500.00 UAH
                'operationAmount' => 50000,
                'currencyCode' => 980,
                'commissionRate' => 0,
                'cashbackAmount' => 0,
                'balance' => 550000,
                'hold' => false,
            ],
        ];

        $transactions = $service->createInsertDataMono($fakeMonoData);
        $this->assertCount(2, $transactions);

        $refundTx = collect($transactions)->first(fn($t) => str_contains($t['source_name'], 'bolt'));
        $incomeTx = collect($transactions)->first(fn($t) => str_contains($t['source_name'], 'поповнення'));

        $this->assertNotNull($refundTx);
        $this->assertEquals(5, $refundTx['transaction_type_id']);
        $this->assertEquals('Повернення коштів', $refundTx['comment']);

        $this->assertNotNull($incomeTx);
        $this->assertEquals(2, $incomeTx['transaction_type_id']);
    }

    public function test_yearly_balance_chart_and_total_amount_exclude_non_flow_transactions(): void
    {
        $user = User::create([
            'name' => 'Chart Balance User',
            'email' => 'chartbalance_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($user);
        UserSetting::create(['user_id' => $user->id, 'currency' => 'UAH']);

        $bill = FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Основний',
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        // Real income: +10 000
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 10000.00,
            'currency_amount' => 10000.00,
            'absolute_currency_amount' => 10000.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 2,
            'is_balance' => 1,
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        // Real expense: -2 500
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => -2500.00,
            'currency_amount' => -2500.00,
            'absolute_currency_amount' => 2500.00,
            'currency_code' => 'UAH',
            'type' => 'expenses',
            'transaction_type_id' => 1,
            'is_balance' => 1,
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        // Initial balance / revision (type 4): +200 000
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 200000.00,
            'currency_amount' => 200000.00,
            'absolute_currency_amount' => 200000.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 4,
            'is_balance' => 1,
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        // Cancellation / refund (type 5): +500
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 500.00,
            'currency_amount' => 500.00,
            'absolute_currency_amount' => 500.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 5,
            'is_balance' => 1,
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        $service = new \App\Services\Finance\Transaction\TransactionsService();
        $service->setTargetUserId($user->id);

        $start = now()->startOfMonth()->format('Y-m-d H:i:s');
        $end = now()->endOfMonth()->format('Y-m-d H:i:s');

        $balanceChart = $service->chartBarBalance('Balance', $start, $end, 'created_at', 'currency_amount');
        $this->assertEquals([7500.0], $balanceChart['values']);
        $this->assertEquals(7500.0, $service->getTotalAmount('currency_amount', false, null, $start, $end));
    }

    public function test_monthly_income_is_grouped_by_accrual_date_with_created_at_fallback(): void
    {
        $user = User::create([
            'name' => 'Accrual Income User',
            'email' => 'accrual_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($user);
        UserSetting::create(['user_id' => $user->id, 'currency' => 'UAH']);

        $bill = FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'ФОП',
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        // Income received in current month (created_at = now), but accrued in previous month
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 30000.00,
            'currency_amount' => 30000.00,
            'absolute_currency_amount' => 30000.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 2,
            'is_balance' => 1,
            'created_at' => now(),
            'accrual_date' => now()->subMonth()->endOfMonth(),
        ]);

        // Income in current month without explicit accrual_date (falls back to created_at)
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 12000.00,
            'currency_amount' => 12000.00,
            'absolute_currency_amount' => 12000.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 2,
            'is_balance' => 1,
            'created_at' => now(),
            'accrual_date' => null,
        ]);

        $dashboardScreen = new DashboardScreen();
        $dashboardData = $dashboardScreen->query(new FinanceTransaction(), new Request());

        $this->assertEquals(
            Currency::convertValueToCurrency(12000.00),
            $dashboardData['metrics']['currentMonth']['income']
        );

        $incomeService = new TransactionIncomeService();
        $incomeService->setTargetUserId($user->id);
        $prevStart = now()->subMonth()->startOfMonth()->format('Y-m-d H:i:s');
        $prevEnd = now()->subMonth()->endOfMonth()->format('Y-m-d H:i:s');
        $this->assertEquals(30000.0, (float)$incomeService->getSum('currency_amount', false, null, $prevStart, $prevEnd));
    }

    public function test_income_edit_preserves_custom_accrual_date_even_with_invoice(): void
    {
        $user = User::create([
            'name' => 'Edit Accrual User',
            'email' => 'editaccrual_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($user);
        UserSetting::create(['user_id' => $user->id, 'currency' => 'UAH']);

        $bill = FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'ФОП',
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        $category = FinanceTransactionCategory::firstOrCreate(
            ['name' => 'ІТ послуги'],
            ['user_id' => $user->id]
        );

        $customer = \App\Models\Customer::create([
            'user_id' => $user->id,
            'name' => 'Test Customer',
        ]);

        $invoice = \App\Models\FinanceInvoice::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'finance_currency_id' => 1,
            'invoice_number' => 'INV-' . uniqid(),
            'total' => 50000,
            'status' => 'not paid',
            'created_at' => '2026-09-19 13:11:40',
        ]);

        $service = new TransactionIncomeService();
        $service->setTargetUserId($user->id);

        // Explicit accrual_date in June must NOT be overwritten by invoice created_at (September)
        $reqWithDate = new Request([
            'transaction' => [
                'finance_bill_id' => $bill->id,
                'transaction_category_id' => $category->id,
                'finance_invoice_id' => $invoice->id,
                'amount' => 50000,
                'accrual_date' => '2026-06-30 12:00:00',
                'created_at' => '2026-07-28 00:13:00',
            ],
        ]);
        $data = $service->createInsertData($reqWithDate);
        $this->assertEquals('2026-06-30 12:00:00', $data['accrual_date']);

        // Empty accrual_date still defaults to invoice created_at
        $reqWithoutDate = new Request([
            'transaction' => [
                'finance_bill_id' => $bill->id,
                'transaction_category_id' => $category->id,
                'finance_invoice_id' => $invoice->id,
                'amount' => 50000,
                'accrual_date' => null,
                'created_at' => '2026-07-28 00:13:00',
            ],
        ]);
        $dataDefault = $service->createInsertData($reqWithoutDate);
        $this->assertEquals('2026-09-19 13:11:40', \Carbon\Carbon::parse($dataDefault['accrual_date'])->format('Y-m-d H:i:s'));
    }
}
