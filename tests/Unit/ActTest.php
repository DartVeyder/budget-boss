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
            'address' => 'м. Київ, вул. Хрещатик, 1',
            'phone' => '+38 (044) 111-22-33',
        ]);

        $act = Act::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'act_number' => 'АКТ-2026/09-77',
            'act_date' => '2026-09-18',
            'customer_tax_group' => '3 група',
            'customer_is_single_tax' => true,
            'customer_is_vat_payer' => false,
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
        $response->assertSee('Адреса: м. Київ, вул. Хрещатик, 1');
        $response->assertSee('+38 (044) 111-22-33');
        $response->assertSee('<div>Платник єдиного податку, 3 група,</div>', false);
        $response->assertSee('<div>Не платник ПДВ</div>', false);
    }

    public function test_customer_details_with_counterparty_address_and_phone(): void
    {
        $user = User::first() ?? User::factory()->create();

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Замовник ' . uniqid(),
            'address' => 'Адреса замовника',
            'phone' => '+380501111111',
        ]);

        $counterparty = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'ФОП Контрагент',
            'address' => 'Адреса ФОП контрагента',
            'phone' => '+380672222222',
            'tax_group' => '2 група',
            'is_single_tax' => true,
            'is_vat_payer' => false,
            'is_active' => true,
        ]);

        $service = new DocumentGenerationService();
        $details = $service->getCustomerDetails($customer, $counterparty);

        $this->assertEquals('Адреса ФОП контрагента', $details['address']);
        $this->assertEquals('+380672222222', $details['phone']);
        $this->assertEquals('Платник єдиного податку, 2 група, Не платник ПДВ', $details['tax_info']);

        // Tax info formatting helper tests
        $this->assertEquals('Платник єдиного податку, 3 група, Платник ПДВ', $service->formatTaxInfo(true, '3 група', true));
        $this->assertEquals('Платник єдиного податку, Не платник ПДВ', $service->formatTaxInfo(true, null, false));
        $this->assertEquals('Не платник ПДВ', $service->formatTaxInfo(false, null, false));
        $this->assertEquals('Платник ПДВ', $service->formatTaxInfo(false, null, true));

        $act = new Act([
            'customer_address' => 'Специфічна адреса для цього акту',
            'customer_phone' => '+380993333333',
            'customer_tax_group' => '3 група',
            'customer_is_single_tax' => true,
            'customer_is_vat_payer' => true,
        ]);
        $detailsWithAct = $service->getCustomerDetails($customer, $counterparty, $act);
        $this->assertEquals('Специфічна адреса для цього акту', $detailsWithAct['address']);
        $this->assertEquals('+380993333333', $detailsWithAct['phone']);
        $this->assertEquals('Платник єдиного податку, 3 група, Платник ПДВ', $detailsWithAct['tax_info']);
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
        $response->assertSee('Отримувач');
        $response->assertSee('Банк отримувача');
        $response->assertSee('КРЕДИТ рах. №');
        $response->assertSee('Постачальник:');
        $response->assertSee('Покупець:');
        $response->assertSee('Товари (роботи, послуги)');
        $response->assertSee('Кількість');
        $response->assertSee('Ціна, грн');
        $response->assertSee('Сума, грн');
        $response->assertSee('Разом:');
        $response->assertSee('Всього найменувань');
        $response->assertSee('Виписав(ла):');
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

    public function test_act_parties_listener_auto_selects_counterparty(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Замовник з ФОП ' . uniqid(),
        ]);

        $counterparty = CustomerCounterparty::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'name' => 'ФОП Платник ' . uniqid(),
            'ipn' => '3018200323',
            'iban' => 'UA193052990000026004021049599',
            'is_active' => true,
        ]);

        $editScreen = new ActEditScreen();
        $result = $editScreen->asyncGetCustomerParties(['customer_id' => $customer->id]);

        $this->assertArrayHasKey('act', $result);
        $this->assertEquals($customer->id, $result['act']['customer_id']);
        $this->assertEquals($counterparty->id, $result['act']['counterparty_id']);
    }

    public function test_fop_contract_fields_and_auto_fill_into_acts_and_invoices(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $fop = Fop::where('user_id', $user->id)->first() ?? Fop::create([
            'user_id' => $user->id,
            'name' => 'ФОП Тестовий Договір',
            'is_active' => true,
        ]);

        $fop->update([
            'contract_number' => 'МД18092026-01',
            'contract_date' => '2026-09-18',
        ]);

        $service = new DocumentGenerationService();

        // 1. Test parsing contract string
        $parsed1 = $service->parseContractDetails('Договір надання послуг:МД18092026-01 від 18 вересня 2026р.');
        $this->assertEquals('МД18092026-01', $parsed1['number']);
        $this->assertEquals('2026-09-18', $parsed1['date']);

        $parsed2 = $service->parseContractDetails('Договір № 99/2026 від 05.10.2026');
        $this->assertEquals('99/2026', $parsed2['number']);
        $this->assertEquals('2026-10-05', $parsed2['date']);

        // 2. Test auto-population in Act query
        $actScreen = new ActEditScreen();
        $queryResult = $actScreen->query(new Act(), new Request(), $service);
        $act = $queryResult['act'];
        $this->assertEquals('МД18092026-01', $act->contract_number);
        $this->assertEquals('2026-09-18', $act->contract_date ? \Carbon\Carbon::parse($act->contract_date)->format('Y-m-d') : null);

        // 3. Test async listener returns FOP contract
        $listenerResult = $actScreen->asyncGetCustomerParties(['fop_id' => $fop->id]);
        $this->assertEquals('МД18092026-01', $listenerResult['act']['contract_number']);
        $this->assertEquals('2026-09-18', $listenerResult['act']['contract_date']);

        // 4. Test invoice print auto-pulls contract from FOP when empty on invoice
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт ' . uniqid(),
            'fop_id' => $fop->id,
        ]);

        $invoice = FinanceInvoice::create([
            'user_id' => $user->id,
            'fop_id' => $fop->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'РАХ-ДОГ-01',
            'invoice_date' => '2026-09-18',
            'contract_number' => null, // empty, should fallback to FOP
            'contract_date' => null,
            'total' => 15000.00,
            'status' => 'not paid',
            'finance_currency_id' => 1,
        ]);

        $response = $this->get(route('platform.invoices.print', $invoice));
        $response->assertOk();
        $response->assertSee('МД18092026-01');
        $response->assertSee('18 вересня 2026');

        // 5. Test FOP screen renders with new fields
        $fopScreenResponse = $this->get(route('platform.fops'));
        $fopScreenResponse->assertOk();
    }

    public function test_signed_act_and_invoice_attachments(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $fop = Fop::where('user_id', $user->id)->first() ?? Fop::create([
            'user_id' => $user->id,
            'name' => 'ФОП Підписані Документи',
            'ipn' => '1122334455',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт ' . uniqid(),
            'fop_id' => $fop->id,
        ]);

        // 1. Test Act attachment relationship and ActEditScreen layout
        $act = Act::create([
            'user_id' => $user->id,
            'fop_id' => $fop->id,
            'customer_id' => $customer->id,
            'act_number' => 'АКТ-ПІДП-01',
            'act_date' => '2026-09-19',
            'status' => 'draft',
            'total_amount' => 10000.00,
            'currency_code' => '980',
        ]);

        $this->assertTrue(method_exists($act, 'attachment'));

        $actEditScreen = new ActEditScreen();
        $layouts = $actEditScreen->layout();
        $this->assertNotEmpty($layouts);

        // 2. Test ActListScreen saveSignedAct
        $actListScreen = new \App\Orchid\Screens\Finance\Act\ActListScreen();
        $actListScreen->saveSignedAct(new Request([
            'act' => [
                'id' => $act->id,
                'status' => 'signed',
                'attachment' => [],
            ],
        ]));

        $this->assertEquals('signed', $act->fresh()->status);

        // 3. Test FinanceInvoice attachment relationship and InvoiceListScreen saveSignedInvoice
        $invoice = FinanceInvoice::create([
            'user_id' => $user->id,
            'fop_id' => $fop->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'РАХ-ПІДП-01',
            'invoice_date' => '2026-09-19',
            'total' => 10000.00,
            'status' => 'not paid',
            'finance_currency_id' => 1,
        ]);

        $this->assertTrue(method_exists($invoice, 'attachment'));

        $invoiceListScreen = new \App\Orchid\Screens\Finance\Invoice\InvoiceListScreen();
        $invoiceListScreen->saveSignedInvoice(new Request([
            'invoice' => [
                'id' => $invoice->id,
                'status' => 'paid',
                'attachment' => [],
            ],
        ]));

        $this->assertEquals('paid', $invoice->fresh()->status);

        // 4. Test HTTP GET on acts and invoices lists return 200
        $this->get(route('platform.acts'))->assertOk();
        $this->get(route('platform.invoices'))->assertOk();
    }

    public function test_import_acts_and_invoices_from_transactions(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $bill = FinanceBill::first() ?? FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Рахунок ' . uniqid(),
            'finance_currency_id' => 1,
            'currency_code' => '980',
            'is_active' => true,
        ]);

        $fop = Fop::create([
            'user_id' => $user->id,
            'name' => 'ФОП Імпорт ' . uniqid(),
            'ipn' => '9988776655',
            'finance_bill_id' => $bill->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Клієнт для імпорту ' . uniqid(),
            'fop_id' => $fop->id,
        ]);

        $tx = FinanceTransaction::create([
            'user_id' => $user->id,
            'fop_id' => $fop->id,
            'customer_id' => $customer->id,
            'finance_bill_id' => $bill->id,
            'transaction_type_id' => 2,
            'type' => 'income',
            'amount' => 25000.00,
            'currency_amount' => 25000.00,
            'currency_code' => '980',
            'finance_currency_id' => 1,
            'is_balance' => true,
            'accrual_date' => '2026-08-10',
        ]);

        // Create a mock attachment for this transaction
        $attachment = \Orchid\Attachment\Models\Attachment::create([
            'name' => 'act_test_' . uniqid(),
            'original_name' => 'Акт_надання_послуг_№_20260810_01_від_10_серпня_2026.pdf',
            'mime' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'sort' => 0,
            'path' => '2026/08/10/',
            'user_id' => $user->id,
        ]);
        $tx->attachment()->attach($attachment->id);

        $this->assertTrue($tx->fresh()->attachment->isNotEmpty());

        // Run batch import service
        $service = app(DocumentGenerationService::class);
        $result = $service->importFromTransactions($user->id);

        $this->assertGreaterThanOrEqual(1, $result['imported']);

        // Check created Act
        $createdAct = Act::where('finance_transaction_id', $tx->id)->first();
        $this->assertNotNull($createdAct);
        $this->assertEquals(25000.00, $createdAct->total_amount);
        $this->assertEquals('signed', $createdAct->status);
        $this->assertTrue($createdAct->attachment->contains('id', $attachment->id));

        // Check created Invoice
        $createdInvoice = FinanceInvoice::find($createdAct->finance_invoice_id);
        $this->assertNotNull($createdInvoice);
        $this->assertEquals(25000.00, $createdInvoice->total);
        $this->assertEquals('paid', $createdInvoice->status);

        // Check commandBar in ActListScreen
        $actListScreen = new \App\Orchid\Screens\Finance\Act\ActListScreen();
        $commands = $actListScreen->commandBar();
        $this->assertNotEmpty($commands);
    }
}
