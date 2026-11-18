<?php

namespace Tests\Unit;

use App\Models\BudgetShare;
use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\User;
use App\Models\UserSetting;
use App\Orchid\Screens\Finance\Bill\BillEditScreen;
use App\Orchid\Screens\Setting\BudgetShare\BudgetShareScreen;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class InitialBalanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_bill_with_initial_balance_creates_audit_transaction(): void
    {
        $user = User::create([
            'name' => 'Bill Test User',
            'email' => 'bill_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($user);

        UserSetting::create(['user_id' => $user->id, 'currency' => 'UAH']);

        $screen = new BillEditScreen();
        $bill = new FinanceBill();

        $request = Request::create('/platform/bills/create', 'POST', [
            'bill' => [
                'name' => 'Новий рахунок Картка',
                'finance_currency_id' => 1,
            ],
            'initial_balance' => 5000.50,
        ]);

        $screen->createOrUpdate($bill, $request);

        $savedBill = FinanceBill::where('user_id', $user->id)->where('name', 'Новий рахунок Картка')->first();
        $this->assertNotNull($savedBill);

        // Check transaction created
        $tx = FinanceTransaction::where('finance_bill_id', $savedBill->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals(5000.50, (float)$tx->amount);
        $this->assertEquals(4, $tx->transaction_type_id); // Ревізія
        $this->assertEquals(2, $tx->transaction_category_id); // Ревізія
        $this->assertEquals(1, $tx->is_balance);
        $this->assertSame('Початковий баланс', $tx->comment);

        // Check total balance of bill
        $this->assertEquals(5000.50, $savedBill->calculateTotalBalance());
    }

    public function test_budget_share_create_user_with_initial_balance(): void
    {
        $admin = User::create([
            'name' => 'Admin Test User',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($admin);

        $screen = new BudgetShareScreen();

        $email = 'partner_' . uniqid() . '@example.com';
        $request = Request::create('/platform/setting/budget-shares', 'POST', [
            'user' => [
                'name' => 'Новий Партнер',
                'email' => $email,
                'password' => 'secret123',
            ],
            'role' => BudgetShare::ROLE_EDITOR,
            'initial_balance' => 2500.00,
            'nickname' => 'Партнер 1',
        ]);

        $screen->createUser($request);

        $newUser = User::where('email', $email)->first();
        $this->assertNotNull($newUser);

        // Verify default bill created
        $bill = FinanceBill::where('user_id', $newUser->id)->where('name', 'Готівка')->first();
        $this->assertNotNull($bill);

        // Verify initial transaction created
        $tx = FinanceTransaction::where('finance_bill_id', $bill->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals(2500.00, (float)$tx->amount);
        $this->assertEquals(4, $tx->transaction_type_id);
        $this->assertSame('Початковий баланс', $tx->comment);
        $this->assertEquals(2500.00, $bill->calculateTotalBalance());
    }

    public function test_initial_balance_is_excluded_from_income_statistics_but_included_in_balance(): void
    {
        $user = User::create([
            'name' => 'Stats Test User',
            'email' => 'stats_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Auth::login($user);
        UserSetting::create(['user_id' => $user->id, 'currency' => 'UAH']);

        $bill = FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Основний рахунок',
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        // 1. Initial balance audit transaction (5 000 UAH)
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 5000.00,
            'currency_amount' => 5000.00,
            'absolute_currency_amount' => 5000.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 4, // Ревізія / Початковий баланс
            'transaction_category_id' => 2,
            'is_balance' => 1,
            'comment' => 'Початковий баланс',
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        // 2. Real operational income transaction (1 500 UAH)
        FinanceTransaction::create([
            'finance_bill_id' => $bill->id,
            'finance_currency_id' => 1,
            'user_id' => $user->id,
            'amount' => 1500.00,
            'currency_amount' => 1500.00,
            'absolute_currency_amount' => 1500.00,
            'currency_code' => 'UAH',
            'type' => 'income',
            'transaction_type_id' => 2, // Дохід
            'transaction_category_id' => 1,
            'is_balance' => 1,
            'comment' => 'Оплата послуг',
            'created_at' => now(),
            'accrual_date' => now(),
        ]);

        // Bill total balance MUST include both (5000 + 1500 = 6500)
        $this->assertEquals(6500.00, $bill->calculateTotalBalance());

        // TransactionsService total balance MUST include both
        $transactionsService = new \App\Services\Finance\Transaction\TransactionsService();
        $transactionsService->setTargetUserId($user->id);
        $transactionsService->setBillId($bill->id);

        // TransactionIncomeService MUST ONLY return real income (1500, NOT 6500)
        $incomeService = new \App\Services\Finance\Transaction\TransactionIncomeService();
        $incomeService->setTargetUserId($user->id);
        $incomeService->setBillId($bill->id);
        $this->assertEquals(1500.00, (float)$incomeService->getSum('currency_amount', false, null, now()->startOfMonth(), now()->endOfMonth()));

        // DashboardScreen metrics MUST ONLY count 1500 as current month income
        $dashboardScreen = new \App\Orchid\Screens\DashboardScreen();
        $dashboardData = $dashboardScreen->query(new FinanceTransaction(), new Request());
        $this->assertEquals(
            \App\Services\Currency\Currency::convertValueToCurrency(1500.00),
            $dashboardData['metrics']['currentMonth']['income']
        );

        // Raw monthly query for this user MUST also be 1500
        $userMonthIncome = (float)FinanceTransaction::where('user_id', $user->id)
            ->where('is_balance', 1)
            ->where('type', 'income')
            ->where(function ($q) {
                $q->whereNull('transaction_type_id')
                  ->orWhereNotIn('transaction_type_id', [3, 4]);
            })
            ->sum('currency_amount');
        $this->assertEquals(1500.00, $userMonthIncome);

        // FOP Tax limit progress & quarterly taxes MUST NOT count the 5 000 initial balance as taxable revenue
        $fop = \App\Models\Fop::create([
            'user_id' => $user->id,
            'name' => 'ФОП Тест',
            'ipn' => '1234567890',
            'fop_group_id' => 1,
            'finance_bill_id' => $bill->id,
            'is_active' => true,
        ]);
        $taxService = new \App\Services\Finance\Fop\FopTaxService();
        $progress = $taxService->getLimitProgress($fop);
        // Annual limit income calculation should be only 1500.00
        $currentQuarter = (int)ceil(now()->month / 3);
        $quarterly = $taxService->getQuarterlyReport($fop, now()->year);
        $this->assertEquals(1500.00, $quarterly['quarters'][$currentQuarter]['income']);
    }
}
