<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\Fop;
use App\Models\User;
use App\Orchid\Screens\Finance\Transaction\TransactionEditScreen;
use App\Services\Finance\Fop\FopTaxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerCounterpartyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_have_multiple_counterparties(): void
    {
        $user = User::first() ?? User::factory()->create();

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт ТОВ Тест ' . uniqid(),
            'is_fop' => false,
        ]);

        $cp1 = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'ФОП Платник Перший',
            'ipn' => '1234567890',
            'iban' => 'UA112233445566778899001122334',
            'bank_name' => 'ПриватБанк',
            'is_active' => true,
        ]);

        $cp2 = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'ФОП Платник Другий',
            'ipn' => '9876543210',
            'iban' => 'UA998877665544332211009988776',
            'bank_name' => 'Монобанк',
            'is_active' => true,
        ]);

        $counterparties = $customer->fresh()->counterparties;
        $this->assertCount(2, $counterparties);
        $this->assertTrue($counterparties->contains('name', 'ФОП Платник Перший'));
        $this->assertTrue($counterparties->contains('name', 'ФОП Платник Другий'));

        // Test full_title accessor
        $this->assertStringContainsString('ФОП Платник Перший', $cp1->full_title);
        $this->assertStringContainsString('1234567890', $cp1->full_title);
    }

    public function test_income_transaction_with_counterparty_relationship(): void
    {
        $user = User::first() ?? User::factory()->create();

        $bill = FinanceBill::first() ?? FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Рахунок ' . uniqid(),
            'finance_currency_id' => 1,
            'currency_code' => '980',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт ' . uniqid(),
        ]);

        $counterparty = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'ФОП Підрядник ' . uniqid(),
            'ipn' => '3344556677',
            'is_active' => true,
        ]);

        $tx = FinanceTransaction::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'counterparty_id' => $counterparty->id,
            'finance_bill_id' => $bill->id,
            'transaction_type_id' => 2,
            'type' => 'income',
            'amount' => 15000.00,
            'currency_amount' => 15000.00,
            'currency_code' => '980',
            'finance_currency_id' => 1,
            'is_balance' => true,
        ]);

        $this->assertNotNull($tx->fresh()->counterparty);
        $this->assertEquals($counterparty->name, $tx->fresh()->counterparty->name);
    }

    public function test_fop_tax_service_income_ledger_displays_counterparty_as_payer(): void
    {
        $user = User::first() ?? User::factory()->create();

        $bill = FinanceBill::first();
        if (!$bill) {
            $bill = FinanceBill::create([
                'user_id' => $user->id,
                'name' => 'Основний рахунок',
                'finance_currency_id' => 1,
                'currency_code' => '980',
                'is_active' => true,
            ]);
        }

        $fop = Fop::where('user_id', $user->id)->first();
        if (!$fop) {
            $fop = Fop::create([
                'user_id' => $user->id,
                'name' => 'Мій ФОП',
                'finance_bill_id' => $bill->id,
                'is_active' => true,
            ]);
        }

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Корпоративний Замовник ' . uniqid(),
        ]);

        $counterparty = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'ФОП Відправник Коштів ' . uniqid(),
            'ipn' => '2233445566',
            'is_active' => true,
        ]);

        $testDate = Carbon::create(2026, 2, 15, 12, 0, 0);

        FinanceTransaction::create([
            'user_id' => $user->id,
            'fop_id' => $fop->id,
            'finance_bill_id' => $bill->id,
            'customer_id' => $customer->id,
            'counterparty_id' => $counterparty->id,
            'transaction_type_id' => 2,
            'type' => 'income',
            'amount' => 50000.00,
            'currency_amount' => 50000.00,
            'currency_code' => '980',
            'finance_currency_id' => 1,
            'is_balance' => true,
            'created_at' => $testDate,
        ]);

        $taxService = new FopTaxService();
        $ledger = $taxService->getIncomeLedger($fop, 2026, 1);

        $matchingRecord = $ledger->first(fn ($r) => $r->get('payer') === $counterparty->name);

        $this->assertNotNull($matchingRecord, 'Counterparty should be listed as payer in ledger');
        $this->assertEquals('2233445566', $matchingRecord->get('payer_edrpou'));
        $this->assertStringContainsString($customer->name, $matchingRecord->get('description'));
        $this->assertStringContainsString($counterparty->name, $matchingRecord->get('description'));
    }

    public function test_async_get_customer_defaults_auto_selects_single_counterparty(): void
    {
        $user = User::first() ?? User::factory()->create();

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт з одним ФОПом ' . uniqid(),
        ]);

        $counterparty = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'Єдиний ФОП платник',
            'ipn' => '1112223334',
            'is_active' => true,
        ]);

        $screen = new TransactionEditScreen();
        $defaults = $screen->asyncGetCustomerDefaults(['customer_id' => $customer->id]);

        $this->assertEquals($counterparty->id, $defaults['transaction']['counterparty_id']);
    }

    public function test_customer_edit_screen_renders_for_existing_customer(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт для перевірки екрану ' . uniqid(),
        ]);

        $screen = new \App\Orchid\Screens\Customer\CustomerEditScreen();
        $query = $screen->query($customer);

        $this->assertArrayHasKey('customer', (array)$query);
        $this->assertArrayHasKey('counterparties', (array)$query);

        $layouts = $screen->layout();
        $this->assertNotEmpty($layouts);
    }
}
