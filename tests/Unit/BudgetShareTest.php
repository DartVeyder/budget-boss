<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\BudgetShare;
use App\Models\FinanceBill;
use App\Models\FinanceTransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BudgetShareTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_shares_budget_bidirectionally(): void
    {
        $u1 = User::create([
            'name' => 'User One Test',
            'email' => 'u1_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $u2 = User::create([
            'name' => 'User Two Test',
            'email' => 'u2_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        // Prior to sharing
        $this->assertSame([$u1->id], $u1->getSharedUserIds());
        $this->assertFalse($u1->isSharingBudget());
        $this->assertSame([$u2->id], $u2->getSharedUserIds());
        $this->assertFalse($u2->isSharingBudget());

        // Create share link
        BudgetShare::create([
            'user_id' => $u1->id,
            'partner_id' => $u2->id,
            'nickname' => 'Партнер',
        ]);

        // Both users should now see both IDs
        $this->assertTrue($u1->isSharingBudget());
        $this->assertTrue($u2->isSharingBudget());

        $this->assertEqualsCanonicalizing([$u1->id, $u2->id], $u1->getSharedUserIds());
        $this->assertEqualsCanonicalizing([$u1->id, $u2->id], $u2->getSharedUserIds());
    }

    public function test_finance_bill_scope_user_includes_shared_bills(): void
    {
        $u1 = User::create([
            'name' => 'User Bill Test 1',
            'email' => 'ub1_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $u2 = User::create([
            'name' => 'User Bill Test 2',
            'email' => 'ub2_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $bill1 = FinanceBill::create([
            'name' => 'Bill U1',
            'user_id' => $u1->id,
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        $bill2 = FinanceBill::create([
            'name' => 'Bill U2',
            'user_id' => $u2->id,
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        // Link users
        BudgetShare::create([
            'user_id' => $u1->id,
            'partner_id' => $u2->id,
        ]);

        $this->actingAs($u1);
        $visibleBills = FinanceBill::user()->pluck('id');

        $this->assertTrue($visibleBills->contains($bill1->id));
        $this->assertTrue($visibleBills->contains($bill2->id));
    }

    public function test_dashboard_and_analytic_and_transaction_screen_queries_with_user_filter(): void
    {
        $u1 = User::create([
            'name' => 'User Screen Test 1',
            'email' => 'ust1_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $u2 = User::create([
            'name' => 'User Screen Test 2',
            'email' => 'ust2_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $bill1 = FinanceBill::create([
            'name' => 'Bill U1 Screen',
            'user_id' => $u1->id,
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        $bill2 = FinanceBill::create([
            'name' => 'Bill U2 Screen',
            'user_id' => $u2->id,
            'finance_currency_id' => 1,
            'currency_code' => 'UAH',
        ]);

        \App\Models\FinanceTransaction::create([
            'user_id' => $u1->id,
            'finance_bill_id' => $bill1->id,
            'amount' => 1000,
            'currency_amount' => 1000,
            'type' => 'income',
            'is_balance' => 1,
            'currency_code' => 'UAH',
            'finance_currency_id' => 1,
            'transaction_type_id' => 1,
            'transaction_category_id' => 1,
            'created_at' => now(),
        ]);

        \App\Models\FinanceTransaction::create([
            'user_id' => $u2->id,
            'finance_bill_id' => $bill2->id,
            'amount' => -400,
            'currency_amount' => -400,
            'type' => 'expenses',
            'is_balance' => 1,
            'currency_code' => 'UAH',
            'finance_currency_id' => 1,
            'transaction_type_id' => 1,
            'transaction_category_id' => 1,
            'created_at' => now(),
        ]);

        BudgetShare::create([
            'user_id' => $u1->id,
            'partner_id' => $u2->id,
        ]);

        $this->actingAs($u1);

        // 1. Dashboard query - Combined
        $dashboardScreen = new \App\Orchid\Screens\DashboardScreen();
        $requestCombined = new \Illuminate\Http\Request();
        $dashData = $dashboardScreen->query(new \App\Models\FinanceTransaction(), $requestCombined);

        $this->assertTrue($dashData['is_sharing_budget']);
        $this->assertCount(2, $dashData['family_breakdown']);
        $this->assertNull($dashData['filter_user_id']);

        // Dashboard query - Filtered to U2
        $requestFiltered = new \Illuminate\Http\Request(['user_id' => $u2->id]);
        $dashFilteredData = $dashboardScreen->query(new \App\Models\FinanceTransaction(), $requestFiltered);
        $this->assertEquals($u2->id, $dashFilteredData['filter_user_id']);

        // 2. AnalyticFinance query - Combined
        $analyticScreen = new \App\Orchid\Screens\Analytic\Finance\AnalyticFinanceScreen();
        $analyticData = $analyticScreen->query($requestCombined);

        $this->assertTrue($analyticData['is_sharing_budget']);
        $this->assertCount(2, $analyticData['members_comparison']);
        $this->assertEquals(1000, $analyticData['members_comparison'][0]['income']);
        $this->assertEquals(400, $analyticData['members_comparison'][1]['expenses']);

        // 3. TransactionListScreen query
        $txListScreen = new \App\Orchid\Screens\Finance\Transaction\TransactionListScreen();
        $txData = $txListScreen->query();

        $this->assertTrue($txData['is_sharing_budget']);
        $this->assertCount(2, $txData['user_summary']);
    }

    public function test_system_roles_and_permissions_matrix(): void
    {
        (new \Database\Seeders\RolesAndPermissionsSeeder())->run();

        $adminRole = \Orchid\Platform\Models\Role::where('slug', 'administrator')->first();
        $financierRole = \Orchid\Platform\Models\Role::where('slug', 'financier')->first();
        $partnerRole = \Orchid\Platform\Models\Role::where('slug', 'partner')->first();
        $viewerRole = \Orchid\Platform\Models\Role::where('slug', 'viewer')->first();

        $this->assertNotNull($adminRole);
        $this->assertNotNull($financierRole);
        $this->assertNotNull($partnerRole);
        $this->assertNotNull($viewerRole);

        // Create test user with Financier role
        $financierUser = User::create([
            'name' => 'Financier User',
            'email' => 'fin_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'permissions' => $financierRole->permissions,
        ]);
        $financierUser->roles()->attach($financierRole->id);

        $this->assertTrue($financierUser->hasAccess('platform.finance.transactions'));
        $this->assertTrue($financierUser->hasAccess('platform.finance.bills'));
        $this->assertTrue($financierUser->hasAccess('platform.fop.tax'));
        $this->assertFalse($financierUser->hasAccess('platform.systems.roles'));
        $this->assertFalse($financierUser->hasAccess('platform.systems.users'));

        // Create test user with Viewer role
        $viewerUser = User::create([
            'name' => 'Viewer User',
            'email' => 'view_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'permissions' => $viewerRole->permissions,
        ]);
        $viewerUser->roles()->attach($viewerRole->id);

        $this->assertTrue($viewerUser->hasAccess('platform.finance.dashboard'));
        $this->assertTrue($viewerUser->hasAccess('platform.analytic.finance'));
        $this->assertFalse($viewerUser->hasAccess('platform.finance.transactions'));
        $this->assertFalse($viewerUser->hasAccess('platform.finance.bills'));
        $this->assertFalse($viewerUser->hasAccess('platform.fop.tax'));
    }

    public function test_budget_share_roles_editor_and_viewer_permissions(): void
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $editorPartner = User::create([
            'name' => 'Editor Partner',
            'email' => 'editor_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $viewerPartner = User::create([
            'name' => 'Viewer Partner',
            'email' => 'viewer_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        // Link with Editor role
        $shareEditor = BudgetShare::create([
            'user_id' => $owner->id,
            'partner_id' => $editorPartner->id,
            'role' => BudgetShare::ROLE_EDITOR,
            'can_create_transactions' => true,
            'nickname' => 'Дружина',
        ]);

        $this->assertTrue($shareEditor->isEditor());
        $this->assertFalse($shareEditor->isViewer());
        $this->assertTrue($editorPartner->canEditSharedBudgetWith($owner->id));
        $this->assertTrue($owner->canEditSharedBudgetWith($editorPartner->id));

        // Link with Viewer role
        $shareViewer = BudgetShare::create([
            'user_id' => $owner->id,
            'partner_id' => $viewerPartner->id,
            'role' => BudgetShare::ROLE_VIEWER,
            'can_create_transactions' => false,
            'nickname' => 'Аудитор',
        ]);

        $this->assertTrue($shareViewer->isViewer());
        $this->assertFalse($shareViewer->isEditor());
        $this->assertFalse($viewerPartner->canEditSharedBudgetWith($owner->id));
    }
}
