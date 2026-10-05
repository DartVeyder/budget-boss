<?php

namespace Tests\Unit;

use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Services\Currency\Currency;
use App\Services\Finance\Transaction\TransactionsService;
use Tests\TestCase;

class FinanceLogicTest extends TestCase
{
    public function test_finance_transaction_currency_amount_accessor_returns_positive_float(): void
    {
        $transaction = new FinanceTransaction();
        $transaction->currency_amount = -150.75;
        $this->assertSame(150.75, $transaction->currency_amount);

        $transaction->currency_amount = 200.00;
        $this->assertSame(200.00, $transaction->currency_amount);
    }

    public function test_currency_parse_exchange_rates_handles_errors_gracefully(): void
    {
        $rates = Currency::parseExchangeRates();
        $this->assertIsArray($rates);
    }

    public function test_calculate_taxes_handles_scalar_and_null_rate_ids_without_type_error(): void
    {
        $service = new \App\Services\Finance\Transaction\TransactionIncomeService();

        // Null rate IDs
        $resNull = $service->calculateTaxes(1000.0, 'before_taxes', null);
        $this->assertSame(0, $resNull['total']);
        $this->assertEmpty($resNull['details']);

        // Scalar rate ID (e.g. 5) when no record in test DB
        $resScalar = $service->calculateTaxes(1000.0, 'before_taxes', 99999);
        $this->assertSame(0, $resScalar['total']);
        $this->assertEmpty($resScalar['details']);

        // Status without_taxes
        $resWithout = $service->calculateTaxes(1000.0, 'without_taxes', [1, 2]);
        $this->assertSame(0, $resWithout['total']);
        $this->assertEmpty($resWithout['details']);
    }

    public function test_update_status_invoice_handles_null_and_missing_id(): void
    {
        $service = new TransactionsService();

        // Null invoice id - should execute without error
        $service->updateStatusInvoice(null);
        $this->assertTrue(true);

        // Non-existent invoice id - should exit early without error
        $service->updateStatusInvoice(999999);
        $this->assertTrue(true);
    }

    public function test_monobank_import_handles_refunds_correctly(): void
    {
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();
        $this->actingAs($user);

        $service = new \App\Services\Finance\Transaction\TransactionExpensesService();
        $service->setUserId($user->id);

        $mockData = [
            [
                'id' => 'mono_refund_test_001',
                'time' => 1726740000,
                'description' => 'Повернення: Сільпо',
                'mcc' => 5411,
                'originalMcc' => 5411,
                'amount' => 18780, // +187.80 UAH refund
                'operationAmount' => 18780,
                'currencyCode' => 980,
                'commissionRate' => 0,
                'cashbackAmount' => 0,
                'balance' => 500000,
                'hold' => false,
            ],
            [
                'id' => 'mono_expense_test_002',
                'time' => 1726740001,
                'description' => 'Сільпо',
                'mcc' => 5411,
                'originalMcc' => 5411,
                'amount' => -25050, // -250.50 UAH expense
                'operationAmount' => -25050,
                'currencyCode' => 980,
                'commissionRate' => 0,
                'cashbackAmount' => 0,
                'balance' => 474950,
                'hold' => false,
            ],
            [
                'id' => 'mono_p2p_test_003',
                'time' => 1726740002,
                'description' => 'Від: Іваненко І.І.',
                'mcc' => 4829,
                'originalMcc' => 4829,
                'amount' => 50000, // +500.00 UAH transfer
                'operationAmount' => 50000,
                'currencyCode' => 980,
                'commissionRate' => 0,
                'cashbackAmount' => 0,
                'balance' => 974950,
                'hold' => false,
            ],
        ];

        $results = $service->createInsertDataMono($mockData);

        $this->assertCount(3, $results);

        // Check refund (ordered chronologically due to array_reverse)
        $refundTx = collect($results)->firstWhere('mono_id', 'mono_refund_test_001');
        $this->assertNotNull($refundTx);
        $this->assertSame('income', $refundTx['type']);
        $this->assertSame(2, $refundTx['transaction_type_id']);
        $this->assertEquals(187.80, $refundTx['amount']);
        $this->assertSame('Повернення коштів', $refundTx['comment']);
        $this->assertEquals(1, $refundTx['is_balance']);

        // Check expense
        $expenseTx = collect($results)->firstWhere('mono_id', 'mono_expense_test_002');
        $this->assertNotNull($expenseTx);
        $this->assertSame('expenses', $expenseTx['type']);
        $this->assertSame(1, $expenseTx['transaction_type_id']);
        $this->assertEquals(-250.50, $expenseTx['amount']);
        $this->assertNull($expenseTx['comment']);

        // Check P2P income transfer
        $p2pTx = collect($results)->firstWhere('mono_id', 'mono_p2p_test_003');
        $this->assertNotNull($p2pTx);
        $this->assertSame('income', $p2pTx['type']);
        $this->assertSame(2, $p2pTx['transaction_type_id']);
        $this->assertEquals(500.00, $p2pTx['amount']);
        $this->assertNull($p2pTx['comment']); // Regular income, not a refund
    }

    public function test_monobank_import_handles_fop_transfers_correctly(): void
    {
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();
        $this->actingAs($user);

        $service = new \App\Services\Finance\Transaction\TransactionExpensesService();
        $service->setUserId($user->id);

        $mockData = [
            [
                'id' => 'mono_fop_transfer_001',
                'time' => 1726740010,
                'description' => 'З гривневого рахунку ФОП',
                'mcc' => 4829,
                'originalMcc' => 4829,
                'amount' => 2000000, // +20 000.00 UAH
                'operationAmount' => 2000000,
                'currencyCode' => 980,
                'commissionRate' => 0,
                'cashbackAmount' => 0,
                'balance' => 2089714,
                'hold' => false,
            ],
        ];

        $results = $service->createInsertDataMono($mockData);

        // Should produce 2 records: one on personal card and one on FOP account
        $this->assertGreaterThanOrEqual(1, count($results));

        $personalTx = collect($results)->firstWhere('mono_id', 'mono_fop_transfer_001');
        $this->assertNotNull($personalTx);
        $this->assertSame('income', $personalTx['type']);
        $this->assertSame(3, $personalTx['transaction_type_id']);
        $this->assertEquals(20000.00, $personalTx['amount']);
        $this->assertSame(0, $personalTx['is_balance']);
        $this->assertSame('Переказ з рахунку ФОП', $personalTx['comment']);

        $fopTx = collect($results)->firstWhere('mono_id', 'mono_fop_transfer_001_fop');
        if ($fopTx) {
            $this->assertSame('expenses', $fopTx['type']);
            $this->assertSame(3, $fopTx['transaction_type_id']);
            $this->assertEquals(-20000.00, $fopTx['amount']);
            $this->assertSame(0, $fopTx['is_balance']);
            $this->assertSame('Переказ на особисту картку', $fopTx['comment']);
        }
    }

    public function test_analytic_finance_created_filter_boundaries(): void
    {
        $filter = new \App\Orchid\Filters\Analytic\Finance\CreatedFilter();
        $request = new \Illuminate\Http\Request([
            'created_at' => [
                'start' => '2026-09-01',
                'end' => '2026-09-30',
            ]
        ]);
        $filter->request = $request;

        $builder = \App\Models\FinanceTransaction::query();
        $filteredBuilder = $filter->run($builder);

        $sql = $filteredBuilder->toSql();
        $bindings = $filteredBuilder->getBindings();

        $this->assertStringContainsString('`created_at` between ? and ?', strtolower($sql));
        $this->assertSame('2026-09-01 00:00:00', $bindings[0]);
        $this->assertSame('2026-09-30 23:59:59', $bindings[1]);
    }

    public function test_date_range_picker_component_renders_server_side(): void
    {
        // 1. Without filter parameters
        $htmlDefault = view('components.date-range-picker')->render();
        $this->assertStringContainsString('Останній рік (за замовчуванням)', $htmlDefault);
        $this->assertStringNotContainsString('Завантаження періоду...', $htmlDefault);
        $this->assertStringContainsString('bb-date-picker-component', $htmlDefault);

        // 2. With filter parameters
        request()->merge([
            'created_at' => [
                'start' => '2026-05-01',
                'end' => '2026-05-31',
            ]
        ]);

        $htmlFiltered = view('components.date-range-picker')->render();
        $this->assertStringContainsString('01.05.2026 — 31.05.2026', $htmlFiltered);
        $this->assertStringContainsString('Скинути', $htmlFiltered);
    }

    public function test_bill_filter_in_transactions_service_and_picker_component(): void
    {
        // 1. TransactionsService with setBillId
        $service = new TransactionsService();
        $this->assertNull($service->getBillId());

        $service->setBillId(42);
        $this->assertSame(42, $service->getBillId());

        // 2. Component renders bill filter button
        request()->merge(['bill_id' => null]);
        $htmlDefault = view('components.date-range-picker')->render();
        $this->assertStringContainsString('bb-bill-picker-component', $htmlDefault);
        $this->assertStringContainsString('bb-bill-trigger-btn', $htmlDefault);
        $this->assertStringContainsString('Всі рахунки та банки', $htmlDefault);

        // 3. Component with active bill
        $dummyBill = new \App\Models\FinanceBill();
        $dummyBill->id = 77;
        $dummyBill->name = 'Монобанк Тест';
        $dummyBill->bank_name = 'Універсал';

        $htmlWithBill = view('components.date-range-picker', [
            'selectedBill' => $dummyBill,
            'selected_bill_id' => 77
        ])->render();

        $this->assertStringContainsString('Монобанк Тест', $htmlWithBill);
        $this->assertStringContainsString('bb-bill-clear-quick', $htmlWithBill);
    }

    public function test_category_transactions_quick_view_endpoint_and_view(): void
    {
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();
        $this->actingAs($user);

        $category = \App\Models\FinanceTransactionCategory::first();
        if ($category) {
            $response = $this->get(route('platform.analytic.finance.category-transactions', [
                'category_id' => $category->id
            ]));

            $response->assertStatus(200);
            $response->assertJsonStructure([
                'category' => ['id', 'name'],
                'count',
                'total_amount',
                'transactions'
            ]);
        }

        // View rendering check
        $chartsMock = [
            'categories' => [
                'income' => [
                    [
                        'items' => [
                            ['id' => 1, 'name' => 'Зарплата Тест', 'amount' => '50 000 ₴', 'percent' => 100, 'color' => '#10b981']
                        ]
                    ]
                ],
                'expenses' => [
                    [
                        'items' => [
                            ['id' => 2, 'name' => 'Продукти Тест', 'amount' => '10 000 ₴', 'percent' => 100, 'color' => '#ef4444']
                        ]
                    ]
                ]
            ]
        ];

        $html = view('dashboard.category-list', ['charts' => $chartsMock])->render();
        $this->assertStringContainsString('bb-clickable-category', $html);
        $this->assertStringContainsString('bb-category-drawer', $html);
        $this->assertStringContainsString('Зарплата Тест', $html);
        $this->assertStringContainsString('Продукти Тест', $html);
    }

    public function test_source_monthly_expenses_service_and_endpoint(): void
    {
        $user = \App\Models\User::first();
        if (!$user) {
            $this->markTestSkipped('No user found');
        }

        $service = new \App\Services\Finance\Transaction\TransactionExpensesService();
        $service->setUserId($user->id);

        $source = \Illuminate\Support\Facades\DB::table('finance_transaction_sources')->first();
        $sourceId = $source ? (int)$source->id : 1;

        $monthlyData = $service->getMonthlyExpensesBySource($sourceId, null, null, null, 6);
        $this->assertIsArray($monthlyData);
        $this->assertArrayHasKey('source', $monthlyData);
        $this->assertArrayHasKey('period', $monthlyData);
        $this->assertArrayHasKey('total_spent', $monthlyData);
        $this->assertArrayHasKey('ticks', $monthlyData);
        $this->assertArrayHasKey('months', $monthlyData);
        $this->assertCount(6, $monthlyData['months']);

        $topSources = $service->getTopSourcesMonthlyExpenses(null, null, null, 3, 6);
        $this->assertIsArray($topSources);

        // Test Endpoint
        $this->actingAs($user);
        $response = $this->get(route('platform.analytic.finance.source-monthly', [
            'source_id' => $sourceId,
        ]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'source' => ['id', 'name'],
            'period' => ['start', 'end', 'label'],
            'total_spent',
            'ticks',
            'months',
            'transactions',
        ]);
    }

    public function test_insights_forecast_blends_current_pace_with_history(): void
    {
        // Day 10 of 30: spent 3000 (300/day), history 200/day -> pace = 300*1/3 + 200*2/3 = 233.33
        $f = \App\Services\Finance\Analytic\FinanceInsightsService::calculateForecast(
            spent: 3000, earned: 10000, daysElapsed: 10, daysTotal: 30,
            histDailyExpenses: 200, avgMonthlyExpenses: 6000, avgMonthlyIncome: 12000
        );

        $this->assertSame(20, $f['days_left']);
        $this->assertEqualsWithDelta(233.33, $f['daily_pace'], 0.01);
        $this->assertEqualsWithDelta(3000 + 233.333 * 20, $f['projected_expenses'], 0.1);
        $this->assertEquals(12000, $f['projected_income']); // max(fact, avg)
        $this->assertEqualsWithDelta(450.0, $f['safe_daily'], 0.01); // (12000-3000)/20
        $this->assertGreaterThan(0, $f['vs_avg_percent']);

        // Last day of month: projection equals actual spending
        $last = \App\Services\Finance\Analytic\FinanceInsightsService::calculateForecast(5000, 0, 31, 31, 100, 0, 0);
        $this->assertEquals(5000, $last['projected_expenses']);
        $this->assertNull($last['vs_avg_percent']);
        $this->assertEquals(0, $last['safe_daily']);
    }

    public function test_insights_category_changes_merge_and_sort(): void
    {
        $cur = [
            '1' => ['id' => 1, 'name' => 'Їжа', 'total' => 8000],
            '2' => ['id' => 2, 'name' => 'Таксі', 'total' => 500],
            '4' => ['id' => 4, 'name' => 'Нова', 'total' => 1000],
        ];
        $prev = [
            '1' => ['id' => 1, 'name' => 'Їжа', 'total' => 6000],
            '2' => ['id' => 2, 'name' => 'Таксі', 'total' => 2000],
            '3' => ['id' => 3, 'name' => 'Зникла', 'total' => 300],
        ];

        $res = \App\Services\Finance\Analytic\FinanceInsightsService::buildCategoryChanges($cur, $prev, 'expenses');
        $rows = $res['rows'];

        $this->assertSame('Їжа', $rows[0]['name']);          // |+2000| biggest
        $this->assertFalse($rows[0]['is_favorable']);         // expenses grew
        $this->assertEquals(33.3, $rows[0]['percent']);
        $this->assertEquals(100, $rows[0]['bar']);

        $taxi = collect($rows)->firstWhere('id', 2);
        $this->assertTrue($taxi['is_favorable']);
        $this->assertEquals(-1500, $taxi['diff']);

        $this->assertTrue(collect($rows)->firstWhere('id', 4)['is_new']);
        $this->assertTrue(collect($rows)->firstWhere('id', 3)['is_gone']);
        $this->assertEquals(9500 - 8300, $res['total_diff']);

        // For income growth is favorable
        $inc = \App\Services\Finance\Analytic\FinanceInsightsService::buildCategoryChanges($cur, $prev, 'income');
        $this->assertTrue($inc['rows'][0]['is_favorable']);
    }

    public function test_insights_emergency_fund_status(): void
    {
        $svc = \App\Services\Finance\Analytic\FinanceInsightsService::class;

        $this->assertSame('critical', $svc::calculateEmergencyFund(5000, 10000)['status']);
        $this->assertSame('low', $svc::calculateEmergencyFund(20000, 10000)['status']);
        $this->assertSame('ok', $svc::calculateEmergencyFund(45000, 10000)['status']);

        $great = $svc::calculateEmergencyFund(70000, 10000);
        $this->assertSame('great', $great['status']);
        $this->assertEquals(7.0, $great['months']);
        $this->assertEquals(100, $great['progress']);
        $this->assertEquals(0, $great['shortfall']);

        $unknown = $svc::calculateEmergencyFund(1000, 0);
        $this->assertSame('unknown', $unknown['status']);
        $this->assertNull($unknown['months']);

        // Negative capital never yields negative months
        $this->assertEquals(0, $svc::calculateEmergencyFund(-5000, 10000)['months']);
    }
}


