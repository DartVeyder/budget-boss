<?php

namespace Tests\Unit;

use App\Models\Fop;
use App\Models\User;
use App\Services\Finance\Fop\FopAnalyticService;
use Tests\TestCase;

class FopAnalyticTest extends TestCase
{
    public function test_fop_analytic_service_returns_correct_kpis_and_charts(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $fop = Fop::where('user_id', $user->id)->first();
        if (!$fop) {
            $this->markTestSkipped('No FOP configured for test user');
        }

        $service = new FopAnalyticService();

        // 1. KPI summary
        $kpi = $service->getKpiSummary($fop, 2026);
        $this->assertIsArray($kpi);
        $this->assertArrayHasKey('total_income', $kpi);
        $this->assertArrayHasKey('net_income', $kpi);
        $this->assertArrayHasKey('total_taxes_accrued', $kpi);
        $this->assertArrayHasKey('limit_progress', $kpi);
        $this->assertGreaterThanOrEqual(0, $kpi['total_income']);

        // 2. Monthly chart
        $chart = $service->getMonthlyRevenueChart($fop, 2026);
        $this->assertIsArray($chart);
        $this->assertCount(2, $chart);
        $this->assertSame(12, count($chart[0]['values']));
        $this->assertSame(12, count($chart[1]['values']));

        // 3. Quarterly breakdown
        $quarterly = $service->getQuarterlyBreakdown($fop, 2026);
        $this->assertIsArray($quarterly);
        $this->assertCount(4, $quarterly);
        $this->assertArrayHasKey('income', $quarterly[1]);
        $this->assertArrayHasKey('total_tax', $quarterly[1]);

        // 4. Customer analytics
        $customers = $service->getCustomerAnalytics($fop, 2026);
        $this->assertIsArray($customers);

        // 5. Compliance
        $compliance = $service->getDocumentCompliance($fop, 2026);
        $this->assertIsArray($compliance);
        $this->assertArrayHasKey('compliance_percent', $compliance);
    }
}
