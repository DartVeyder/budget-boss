<?php

namespace Tests\Unit;

use App\Models\Act;
use App\Models\ActItem;
use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\FinanceBill;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Models\Fop;
use App\Models\User;
use App\Orchid\Screens\Finance\Act\ActEditScreen;
use App\Orchid\Screens\Finance\Act\ActListScreen;
use App\Services\Finance\Act\DocumentGenerationService;
use App\Services\Finance\Act\UkrainianNumberToWords;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ukrainian_number_to_words(): void
    {
        $this->assertEquals(
            "П'ятдесят тисяч гривень 00 копійок, без ПДВ",
            UkrainianNumberToWords::convert(50000)
        );

        $this->assertEquals(
            "Одна тисяча одна гривня 01 копійка, без ПДВ",
            UkrainianNumberToWords::convert(1001.01)
        );

        $this->assertEquals(
            "Двадцять дві гривні 02 копійки, без ПДВ",
            UkrainianNumberToWords::convert(22.02)
        );

        $this->assertEquals(
            "Дванадцять тисяч п'ятсот тридцять чотири гривні 50 копійок, без ПДВ",
            UkrainianNumberToWords::convert(12534.50)
        );

        $this->assertEquals(
            "Тридцять одна тисяча гривень 00 копійок",
            UkrainianNumberToWords::convert(31000, false)
        );

        $this->assertEquals(
            "18 вересня 2026 р.",
            UkrainianNumberToWords::formatUkrainianDate(Carbon::create(2026, 9, 18))
        );
    }

    public function test_act_creation_with_items_and_total(): void
    {
        $user = User::first() ?? User::factory()->create();

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт для Акту ' . uniqid(),
        ]);

        $act = Act::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'act_number' => 'АКТ-2026/09-99',
            'act_date' => '2026-09-18',
            'total_amount' => 45000.00,
            'status' => 'draft',
        ]);

        $act->items()->create([
            'name' => 'Розробка модулів для CRM',
            'unit' => 'послуга',
            'quantity' => 1,
            'price' => 30000.00,
            'amount' => 30000.00,
        ]);

        $act->items()->create([
            'name' => 'Технічна підтримка',
            'unit' => 'послуга',
            'quantity' => 1,
            'price' => 15000.00,
            'amount' => 15000.00,
        ]);

        $this->assertCount(2, $act->fresh()->items);
        $this->assertEquals("Сорок п'ять тисяч гривень 00 копійок, без ПДВ", $act->total_amount_in_words);
    }

    public function test_document_generation_service_from_transaction(): void
    {
        $user = User::first() ?? User::factory()->create();

        $bill = FinanceBill::first() ?? FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Рахунок ФОП',
            'finance_currency_id' => 1,
            'currency_code' => '980',
            'is_active' => true,
        ]);

        $fop = Fop::where('user_id', $user->id)->first() ?? Fop::create([
            'user_id' => $user->id,
            'name' => 'ФОП Творець Документів',
            'finance_bill_id' => $bill->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Замовник Послуг ' . uniqid(),
        ]);

        $counterparty = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'ФОП Платник Замовника',
            'ipn' => '9988776655',
            'is_active' => true,
        ]);

        $tx = FinanceTransaction::create([
            'user_id' => $user->id,
            'fop_id' => $fop->id,
            'finance_bill_id' => $bill->id,
            'customer_id' => $customer->id,
            'counterparty_id' => $counterparty->id,
            'transaction_type_id' => 2,
            'type' => 'income',
            'amount' => 70000.00,
            'currency_amount' => 70000.00,
            'currency_code' => '980',
            'finance_currency_id' => 1,
            'comment' => 'Оплата за створення платформи',
            'is_balance' => true,
        ]);

        $service = new DocumentGenerationService();
        $prepared = $service->prepareFromTransaction($tx);

        $this->assertEquals($customer->id, $prepared['customer_id']);
        $this->assertEquals($counterparty->id, $prepared['counterparty_id']);
        $this->assertEquals(70000.00, $prepared['total_amount']);
        $this->assertEquals('Оплата за створення платформи', $prepared['items'][0]['name']);
    }

    public function test_act_print_page_returns_http_200(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт для Друку ' . uniqid(),
        ]);

        $act = Act::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'act_number' => 'АКТ-2026/09-77',
            'act_date' => '2026-09-18',
            'total_amount' => 20000.00,
            'status' => 'draft',
        ]);

        $act->items()->create([
            'name' => 'Тестова послуга',
            'unit' => 'послуга',
            'quantity' => 1,
            'price' => 20000.00,
            'amount' => 20000.00,
        ]);

        $response = $this->get(route('platform.acts.print', $act));
        $response->assertOk();
        $response->assertSee('АКТ надання послуг');
        $response->assertSee('АКТ-2026/09-77');
        $response->assertSee('Двадцять тисяч гривень 00 копійок');
    }

    public function test_invoice_print_page_returns_http_200(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт для Інвойсу ' . uniqid(),
        ]);

        $invoice = FinanceInvoice::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'РАХ-2026/09-11',
            'invoice_date' => '2026-09-18',
            'total' => 35000.00,
            'status' => 'not paid',
            'finance_currency_id' => 1,
        ]);

        $response = $this->get(route('platform.invoices.print', $invoice));
        $response->assertOk();
        $response->assertSee('Рахунок на оплату');
        $response->assertSee('РАХ-2026/09-11');
        $response->assertSee('без ПДВ');
    }

    public function test_act_screens_query_and_layout(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $listScreen = new ActListScreen();
        $listQuery = $listScreen->query();
        $this->assertArrayHasKey('acts', (array)$listQuery);
        $this->assertNotEmpty($listScreen->layout());

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт для Екрану ' . uniqid(),
        ]);

        $act = Act::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'act_number' => 'АКТ-2026/09-55',
            'act_date' => '2026-09-18',
            'total_amount' => 10000.00,
        ]);

        $editScreen = new ActEditScreen();
        $editQuery = $editScreen->query($act, new Request(), new DocumentGenerationService());
        $this->assertArrayHasKey('act', (array)$editQuery);
        $this->assertArrayHasKey('items', (array)$editQuery);
        $this->assertNotEmpty($editScreen->layout());

        // Test HTTP GET create screen
        $createResponse = $this->get(route('platform.acts.create'));
        $createResponse->assertOk();

        // Test HTTP GET edit screen
        $editResponse = $this->get(route('platform.acts.edit', $act));
        $editResponse->assertOk();

        // Test HTTP GET list screen
        $listResponse = $this->get(route('platform.acts'));
        $listResponse->assertOk();
    }
}
