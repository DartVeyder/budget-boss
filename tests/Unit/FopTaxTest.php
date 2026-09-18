<?php

namespace Tests\Unit;

use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\Fop;
use App\Models\FopGroup;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Finance\Fop\FopTaxService;
use Carbon\Carbon;
use Tests\TestCase;

class FopTaxTest extends TestCase
{
    public function test_fop_effective_limit_fallback_to_group(): void
    {
        $group = new FopGroup(['name' => '3 група', 'annual_limit' => 8285700.00, 'monthly_esv' => 1760.00]);
        $fop = new Fop(['annual_limit' => null, 'custom_esv' => null]);
        $fop->setRelation('fopGroup', $group);

        $this->assertSame(8285700.00, $fop->effective_annual_limit);
        $this->assertSame(1760.00, $fop->effective_monthly_esv);

        // Custom override
        $fop->annual_limit = 5000000.00;
        $fop->custom_esv = 2000.00;
        $this->assertSame(5000000.00, $fop->effective_annual_limit);
        $this->assertSame(2000.00, $fop->effective_monthly_esv);

        // ESV exempt override
        $fop->is_esv_exempt = true;
        $this->assertSame(0.0, $fop->effective_monthly_esv);
    }

    public function test_quarterly_report_with_esv_exemption(): void
    {
        $service = new FopTaxService();
        $group = new FopGroup(['name' => '3 група', 'annual_limit' => 8285700.00, 'monthly_esv' => 1760.00]);
        $fop = new Fop(['id' => 99999, 'user_id' => 99999, 'is_esv_exempt' => true]);
        $fop->setRelation('fopGroup', $group);

        $report = $service->getQuarterlyReport($fop, 2026);
        $this->assertTrue($report['is_esv_exempt']);
        $this->assertSame(0.0, $report['monthly_esv']);
        $this->assertSame(0.0, (float)$report['total_year_esv']);
        foreach ($report['quarters'] as $q) {
            $this->assertSame(0.0, (float)$q['esv']);
        }
    }

    public function test_limit_progress_status_levels(): void
    {
        $service = new FopTaxService();
        $group = new FopGroup(['name' => '3 група', 'annual_limit' => 100000.00]);
        $fop = new Fop(['id' => 99999, 'user_id' => 99999, 'annual_limit' => 100000.00]);
        $fop->setRelation('fopGroup', $group);

        $progress = $service->getLimitProgress($fop, 2026);

        $this->assertIsArray($progress);
        $this->assertSame(2026, $progress['year']);
        $this->assertSame(100000.00, $progress['limit']);
        $this->assertArrayHasKey('percent', $progress);
        $this->assertArrayHasKey('status', $progress);
        $this->assertArrayHasKey('message', $progress);
    }

    public function test_single_tax_rate_correctly_resolves_to_five_percent_when_military_tax_also_attached(): void
    {
        $service = new FopTaxService();
        $group = new FopGroup(['name' => '3 група', 'annual_limit' => 8285700.00, 'monthly_esv' => 1760.00]);
        
        $rateMilitary = new TaxRate(['name' => 'Військовий збір 1%', 'value' => 1.00]);
        $rateMilitary->id = 3;
        $rateSingle = new TaxRate(['name' => 'Єдиний податок 5%', 'value' => 5.00]);
        $rateSingle->id = 4;

        // Note: military tax is first in collection
        $group->setRelation('taxRates', collect([$rateMilitary, $rateSingle]));

        $fop = new Fop(['id' => 99999, 'user_id' => 99999]);
        $fop->setRelation('fopGroup', $group);

        $report = $service->getQuarterlyReport($fop, 2026);
        $this->assertSame(5.0, $report['single_tax_percent']);

        $decl = $service->getDeclarationSummary($fop, 2026, 1);
        $this->assertSame(5.0, $decl['rate_percent']);
    }

    public function test_quarterly_report_contains_all_four_quarters_with_deadlines(): void
    {
        $service = new FopTaxService();
        $group = new FopGroup(['name' => '3 група', 'annual_limit' => 8285700.00, 'monthly_esv' => 1760.00]);
        $fop = new Fop(['id' => 99999, 'user_id' => 99999]);
        $fop->setRelation('fopGroup', $group);

        $report = $service->getQuarterlyReport($fop, 2026);

        $this->assertIsArray($report);
        $this->assertCount(4, $report['quarters']);

        foreach ([1, 2, 3, 4] as $q) {
            $quarter = $report['quarters'][$q];
            $this->assertSame($q, $quarter['quarter']);
            $this->assertArrayHasKey('income', $quarter);
            $this->assertArrayHasKey('single_tax', $quarter);
            $this->assertArrayHasKey('military_tax', $quarter);
            $this->assertArrayHasKey('esv', $quarter);
            $this->assertArrayHasKey('total_tax', $quarter);
            $this->assertArrayHasKey('deadlines', $quarter);

            $deadlines = $quarter['deadlines'];
            $this->assertArrayHasKey('esv', $deadlines);
            $this->assertArrayHasKey('declaration', $deadlines);
            $this->assertArrayHasKey('single_tax', $deadlines);
        }
    }

    public function test_declaration_summary_rows(): void
    {
        $service = new FopTaxService();
        $group = new FopGroup(['name' => '3 група', 'annual_limit' => 8285700.00]);
        $fop = new Fop(['id' => 99999, 'name' => 'ФОП Тест', 'ipn' => '1234567890']);
        $fop->setRelation('fopGroup', $group);

        $decl = $service->getDeclarationSummary($fop, 2026, 2);

        $this->assertIsArray($decl);
        $this->assertSame(2026, $decl['year']);
        $this->assertSame(2, $decl['quarter']);
        $this->assertArrayHasKey('line_income', $decl);
        $this->assertArrayHasKey('line_accrued_tax', $decl);
        $this->assertArrayHasKey('line_previous_tax', $decl);
        $this->assertArrayHasKey('line_payable_tax', $decl);
        $this->assertArrayHasKey('military_tax', $decl);
    }

    public function test_fop_ledger_records_are_repositories_and_build_td_without_error(): void
    {
        $service = new FopTaxService();
        $group = new FopGroup(['name' => '3 група', 'annual_limit' => 8285700.00]);
        $fop = new Fop(['id' => 99999, 'name' => 'ФОП Тест', 'ipn' => '1234567890']);
        $fop->setRelation('fopGroup', $group);

        $records = $service->getIncomeLedger($fop, 2026, 1);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $records);

        // Test TD build with repository
        $repo = new \Orchid\Screen\Repository([
            'index' => 1,
            'date' => '10.01.2026',
            'description' => 'Тест оплата',
            'payer_edrpou' => '12345678',
            'cashless_amount' => 15000.0,
            'total_income' => 15000.0,
            'adjusted_income' => 15000.0,
        ]);

        $tdIndex = \Orchid\Screen\TD::make('index');
        $view = $tdIndex->buildTd($repo);
        $this->assertNotNull($view);

        $tdDesc = \Orchid\Screen\TD::make('description')->render(fn ($r) => $r->get('description'));
        $viewDesc = $tdDesc->buildTd($repo);
        $this->assertNotNull($viewDesc);
    }

    public function test_fop_ledger_screen_renders_http_200(): void
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user in database');
        }

        $response = $this->actingAs($user)->get(route('platform.fop.ledger', ['year' => 2026, 'quarter' => 1]));
        $response->assertOk();
    }

    public function test_fop_ledger_export_and_print_endpoints(): void
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user in database');
        }

        $exportResp = $this->actingAs($user)->get(route('platform.fop.ledger.export', ['year' => 2026, 'quarter' => 1]));
        $exportResp->assertOk();

        $printResp = $this->actingAs($user)->get(route('platform.fop.ledger.print', ['year' => 2026, 'quarter' => 1]));
        $printResp->assertOk();
    }
}
