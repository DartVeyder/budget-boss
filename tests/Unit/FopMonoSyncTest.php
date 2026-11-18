<?php

namespace Tests\Unit;

use App\Models\FinanceBill;
use App\Models\Fop;
use App\Models\FopGroup;
use App\Models\User;
use App\Services\Api\Monobank\Monobank;
use App\Services\Finance\Fop\FopMonoSyncService;
use App\Services\Finance\Transaction\TransactionExpensesService;
use App\Services\Finance\Transaction\TransactionIncomeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FopMonoSyncTest extends TestCase
{
    use DatabaseTransactions;
    protected function getService(): FopMonoSyncService
    {
        return new FopMonoSyncService(
            app(TransactionExpensesService::class),
            app(TransactionIncomeService::class)
        );
    }

    public function test_detect_tax_payment_classification(): void
    {
        $service = $this->getService();
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('detectTaxPayment');
        $method->setAccessible(true);

        // 1. Single tax Q3
        $text1 = "101 сплата єдиного податку за липень-вересень 2026 року";
        $res1 = $method->invoke($service, $text1, Carbon::parse('2026-10-29'));
        $this->assertTrue($res1['is_tax']);
        $this->assertSame('single_tax', $res1['tax_type']);
        $this->assertSame(3, $res1['tax_quarter']);
        $this->assertSame(2026, $res1['tax_year']);

        // 2. Military tax Q3
        $text2 = "101 сплата військового збору за липень-вересень 2026 року";
        $res2 = $method->invoke($service, $text2, Carbon::parse('2026-10-29'));
        $this->assertTrue($res2['is_tax']);
        $this->assertSame('military_tax', $res2['tax_type']);
        $this->assertSame(3, $res2['tax_quarter']);
        $this->assertSame(2026, $res2['tax_year']);

        // 3. ESV Q2
        $text3 = "сплата єдиного внеску єсв за 2-й кв 2026 р.";
        $res3 = $method->invoke($service, $text3, Carbon::parse('2026-07-15'));
        $this->assertTrue($res3['is_tax']);
        $this->assertSame('esv', $res3['tax_type']);
        $this->assertSame(2, $res3['tax_quarter']);
        $this->assertSame(2026, $res3['tax_year']);

        // 4. Non-tax
        $text4 = "оплата за послуги розробки сайту";
        $res4 = $method->invoke($service, $text4, Carbon::now());
        $this->assertFalse($res4['is_tax']);
    }

    public function test_is_self_transfer(): void
    {
        $service = $this->getService();
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('isSelfTransfer');
        $method->setAccessible(true);

        $fop = new Fop([
            'ipn' => '3574000037',
            'user_id' => 1,
        ]);

        // Same EDRPOU
        $this->assertTrue($method->invoke($service, $fop, '3574000037', 'Ім\'я', 'переказ'));

        // Comment with personal card transfer
        $this->assertTrue($method->invoke($service, $fop, '9999999999', 'Інший', 'переказ на картку'));

        // Third party payment
        $this->assertFalse($method->invoke($service, $fop, '12345678', 'ТОВ Ромашка', 'оплата за послуги'));
    }

    public function test_match_invoice_by_number(): void
    {
        $service = $this->getService();
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('matchInvoice');
        $method->setAccessible(true);

        $fullText = "оплата за послуги супроводу згідно з рахунком № 20261105-01 від 5 листопада";
        $invoice = $method->invoke($service, 1, null, null, 20000.0, $fullText);

        $this->assertNotNull($invoice);
        $this->assertSame('20261105-01', $invoice->invoice_number);
    }

    public function test_monobank_account_id_resolution_from_fop_and_bill(): void
    {
        $service = $this->getService();

        $fop = new Fop([
            'monobank_account_id' => 'custom_fop_account_id',
        ]);
        $this->assertSame('custom_fop_account_id', $service->resolveFopAccountId($fop, 'dummy_token'));

        $bill = new FinanceBill([
            'monobank_account_id' => 'bill_account_id',
        ]);
        $fopWithoutAcc = new Fop([
            'monobank_account_id' => null,
        ]);
        $fopWithoutAcc->setRelation('bill', $bill);
        $this->assertSame('bill_account_id', $service->resolveFopAccountId($fopWithoutAcc, 'dummy_token'));
    }

    public function test_accrual_date_is_set_to_end_of_previous_month(): void
    {
        // For a payment on 2026-11-05
        $itemDate1 = Carbon::parse('2026-11-05 19:12:33');
        $accrual1 = $itemDate1->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d 00:00:00');
        $this->assertSame('2026-10-31 00:00:00', $accrual1);

        // For a payment on 2026-10-14
        $itemDate2 = Carbon::parse('2026-10-14 15:29:31');
        $accrual2 = $itemDate2->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d 00:00:00');
        $this->assertSame('2026-09-30 00:00:00', $accrual2);

        // For a payment on 2026-01-10
        $itemDate3 = Carbon::parse('2026-01-10 10:00:00');
        $accrual3 = $itemDate3->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d 00:00:00');
        $this->assertSame('2025-12-31 00:00:00', $accrual3);
    }

    public function test_create_income_transaction_sets_accrual_date_without_error(): void
    {
        $user = User::create([
            'name' => 'Fop Sync Test User',
            'email' => 'fop_sync_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);

        $bill = FinanceBill::create([
            'user_id' => $user->id,
            'name' => 'Рахунок ФОП',
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        $fop = Fop::create([
            'user_id' => $user->id,
            'name' => 'ФОП Тестер',
            'ipn' => '3333333333',
            'finance_bill_id' => $bill->id,
            'fop_group_id' => 1,
            'is_active' => true,
        ]);

        $service = $this->getService();
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('createIncomeTransaction');
        $method->setAccessible(true);

        $paymentTime = Carbon::parse('2026-11-05 14:30:00')->timestamp;
        $mockItem = [
            'id' => 'mono_tx_' . uniqid(),
            'time' => $paymentTime,
            'description' => 'Оплата за послуги за договором #12',
            'comment' => 'Без ПДВ',
            'counterName' => 'ТОВ "Клієнт"',
            'counterEdrpou' => '44444444',
            'counterIban' => 'UA112233445566778899001122334',
            'amount' => 5000000, // +50 000.00 UAH
            'mcc' => 7372,
        ];

        /** @var \App\Models\FinanceTransaction $tx */
        $tx = $method->invoke(
            $service,
            $fop,
            $mockItem,
            50000.00,
            '2026-11-05 14:30:00',
            $mockItem['id'],
            collect(),
            []
        );

        $this->assertNotNull($tx);
        $this->assertEquals(50000.00, (float)$tx->amount);
        $this->assertSame('2026-11-05 14:30:00', Carbon::parse($tx->created_at)->format('Y-m-d H:i:s'));
        // Accrual date goes for the previous month (2026-10-31)
        $this->assertSame('2026-10-31 00:00:00', Carbon::parse($tx->accrual_date)->format('Y-m-d 00:00:00'));
        $this->assertSame(2, $tx->transaction_type_id);
    }
}

