<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FinanceBill;
use App\Models\FinanceCurrency;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\User;
use App\Orchid\Screens\Analytic\AnalyticScreen;
use App\Orchid\Screens\Analytic\Finance\AnalyticFinanceScreen;
use App\Services\Finance\Analytic\IncomeExpenseAnalysisService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class IncomeExpenseAnalysisTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_category_semantic_classification(): void
    {
        // 1. Needs
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_NEEDS, IncomeExpenseAnalysisService::classifyCategory('Продукти та супермаркети'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_NEEDS, IncomeExpenseAnalysisService::classifyCategory('Комунальні послуги та інтернет'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_NEEDS, IncomeExpenseAnalysisService::classifyCategory('Житло'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_NEEDS, IncomeExpenseAnalysisService::classifyCategory('Заправка'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_NEEDS, IncomeExpenseAnalysisService::classifyCategory('Податок'));

        // 2. Wants
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_WANTS, IncomeExpenseAnalysisService::classifyCategory('Кафе та ресторани'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_WANTS, IncomeExpenseAnalysisService::classifyCategory('Одяг та взуття'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_WANTS, IncomeExpenseAnalysisService::classifyCategory('Підписка Netflix'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_WANTS, IncomeExpenseAnalysisService::classifyCategory('Таксі Bolt'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_WANTS, IncomeExpenseAnalysisService::classifyCategory('Розваги'));

        // 3. Savings / Investments
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_SAVINGS, IncomeExpenseAnalysisService::classifyCategory('Депозит'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_SAVINGS, IncomeExpenseAnalysisService::classifyCategory('Заощадження'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_SAVINGS, IncomeExpenseAnalysisService::classifyCategory('Криптовалюта Binance'));

        // 4. Education
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_EDUCATION, IncomeExpenseAnalysisService::classifyCategory('Навчання програмуванню'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_EDUCATION, IncomeExpenseAnalysisService::classifyCategory('Книги'));

        // 5. Long term / Asset repairs
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_LONG_TERM, IncomeExpenseAnalysisService::classifyCategory('Ремонт'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_LONG_TERM, IncomeExpenseAnalysisService::classifyCategory('Побутова техніка'));

        // 6. Give / Charity
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_GIVE, IncomeExpenseAnalysisService::classifyCategory('Подарунки'));
        $this->assertEquals(IncomeExpenseAnalysisService::BUCKET_GIVE, IncomeExpenseAnalysisService::classifyCategory('Донати ЗСУ'));
    }

    public function test_service_analyzes_income_and_expense_with_real_transactions(): void
    {
        $service = new IncomeExpenseAnalysisService($this->user->id);
        $result = $service->analyze();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('allocation_50_30_20', $result);
        $this->assertArrayHasKey('allocation_6_jars', $result);
        $this->assertArrayHasKey('concentration', $result);
        $this->assertArrayHasKey('recommendations', $result);

        $summary = $result['summary'];
        $this->assertGreaterThanOrEqual(0, $summary['period_income']);
        $this->assertGreaterThanOrEqual(0, $summary['period_expenses']);
        $this->assertGreaterThanOrEqual(0, $summary['reference_income']);
    }

    public function test_50_30_20_allocation_rules_and_buckets(): void
    {
        $service = new IncomeExpenseAnalysisService($this->user->id);
        $result = $service->analyze();

        $alloc = $result['allocation_50_30_20'];
        $this->assertArrayHasKey('needs', $alloc);
        $this->assertArrayHasKey('wants', $alloc);
        $this->assertArrayHasKey('savings', $alloc);
        $this->assertArrayHasKey('overall_status', $alloc);

        // Check target percentages
        $this->assertEquals(50, $alloc['needs']['target_pct']);
        $this->assertEquals(30, $alloc['wants']['target_pct']);
        $this->assertEquals(20, $alloc['savings']['target_pct']);

        // Check target amounts proportional to reference income
        $refIncome = $alloc['income_base'];
        $this->assertEquals(round($refIncome * 0.50, 2), $alloc['needs']['target_amount']);
        $this->assertEquals(round($refIncome * 0.30, 2), $alloc['wants']['target_amount']);
        $this->assertEquals(round($refIncome * 0.20, 2), $alloc['savings']['target_amount']);
    }

    public function test_6_jars_allocation_rules(): void
    {
        $service = new IncomeExpenseAnalysisService($this->user->id);
        $result = $service->analyze();

        $jars = $result['allocation_6_jars']['jars'];
        $this->assertCount(6, $jars);

        $codes = array_column($jars, 'code');
        $this->assertEquals(['NEC', 'FFA', 'LTSS', 'EDU', 'PLAY', 'GIVE'], $codes);

        $totalPercent = array_sum(array_column($jars, 'percent'));
        $this->assertEquals(100, $totalPercent);
    }

    public function test_expense_concentration_and_leaks(): void
    {
        $service = new IncomeExpenseAnalysisService($this->user->id);
        $result = $service->analyze();

        $concentration = $result['concentration'];
        $this->assertArrayHasKey('top_categories', $concentration);
        $this->assertArrayHasKey('top_3_share', $concentration);
        $this->assertArrayHasKey('top_5_share', $concentration);
        $this->assertArrayHasKey('leaks', $concentration);

        $leaks = $concentration['leaks'];
        $this->assertArrayHasKey('dining', $leaks);
        $this->assertArrayHasKey('subscriptions', $leaks);
        $this->assertArrayHasKey('taxi', $leaks);
    }

    public function test_recommendations_generation(): void
    {
        $service = new IncomeExpenseAnalysisService($this->user->id);
        $result = $service->analyze();

        $tips = $result['recommendations'];
        $this->assertNotEmpty($tips);

        foreach ($tips as $tip) {
            $this->assertArrayHasKey('type', $tip);
            $this->assertArrayHasKey('icon', $tip);
            $this->assertArrayHasKey('title', $tip);
            $this->assertArrayHasKey('text', $tip);
        }
    }

    public function test_analytic_finance_screen_query_and_layout(): void
    {
        $screen = new AnalyticFinanceScreen();
        $request = new Request();
        $data = $screen->query($request);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('analysis', $data);
        $this->assertArrayHasKey('allocation_50_30_20', $data['analysis']);
        $this->assertArrayHasKey('concentration', $data['analysis']);
        $this->assertArrayHasKey('recommendations', $data['analysis']);

        // Verify default period is current year
        $currentYear = Carbon::now()->year;
        $this->assertEquals("01.01.{$currentYear}", $data['kpi']['period']['start']);
        $this->assertEquals("31.12.{$currentYear}", $data['kpi']['period']['end']);

        $layouts = $screen->layout();
        $this->assertNotEmpty($layouts);
    }

    public function test_analytic_screen_redirects_to_finance(): void
    {
        $screen = new AnalyticScreen();
        $request = new Request();
        $response = $screen->handle($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(route('platform.analytic.finance'), $response->getTargetUrl());
    }

    public function test_monthly_budget_from_previous_month(): void
    {
        $service = new IncomeExpenseAnalysisService($this->user->id);
        $budget = $service->monthlyBudgetFromPreviousMonth();

        $this->assertIsArray($budget);
        $this->assertArrayHasKey('current_month', $budget);
        $this->assertArrayHasKey('prev_month', $budget);
        $this->assertArrayHasKey('plan_50_30_20', $budget);
        $this->assertArrayHasKey('plan_6_jars', $budget);
        $this->assertArrayHasKey('envelopes', $budget);

        $prevInc = $budget['prev_month']['income'];
        $this->assertGreaterThan(0, $prevInc);

        $p = $budget['plan_50_30_20'];
        $this->assertEquals(round($prevInc * 0.50, 2), $p['needs']['allocated']);
        $this->assertEquals(round($prevInc * 0.30, 2), $p['wants']['allocated']);
        $this->assertEquals(round($prevInc * 0.20, 2), $p['savings']['allocated']);

        $envelopes = $budget['envelopes'];
        $this->assertCount(3, $envelopes);
        foreach ($envelopes as $env) {
            $this->assertArrayHasKey('step', $env);
            $this->assertArrayHasKey('title', $env);
            $this->assertArrayHasKey('amount', $env);
            $this->assertArrayHasKey('destination', $env);
            $this->assertArrayHasKey('note', $env);
        }
    }

    public function test_dashboard_screen_contains_monthly_budget_widget(): void
    {
        $screen = new \App\Orchid\Screens\DashboardScreen();
        $request = new Request();
        $data = $screen->query(new \App\Models\FinanceTransaction(), $request);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('monthlyBudget', $data);
        $this->assertArrayHasKey('plan_50_30_20', $data['monthlyBudget']);
        $this->assertArrayHasKey('envelopes', $data['monthlyBudget']);

        $this->assertEquals('Інформаційна панель', $screen->name());
        $layouts = $screen->layout();
        $this->assertNotEmpty($layouts);
    }
}

