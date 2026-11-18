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
}
