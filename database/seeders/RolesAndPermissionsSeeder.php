<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * All system permissions with descriptions.
     */
    public const ALL_PERMISSIONS = [
        'platform.index' => 1,
        'platform.finance.dashboard' => 1,
        'platform.finance.transactions' => 1,
        'platform.finance.bills' => 1,
        'platform.finance.invoices' => 1,
        'platform.finance.acts' => 1,
        'platform.finance.categories' => 1,
        'platform.crypto.binance' => 1,
        'platform.analytic.finance' => 1,
        'platform.fop.analytic' => 1,
        'platform.fop.profile' => 1,
        'platform.fop.tax' => 1,
        'platform.fop.ledger' => 1,
        'platform.customers' => 1,
        'platform.setting.budget-shares' => 1,
        'platform.setting' => 1,
        'platform.setting.taxes' => 1,
        'platform.systems.roles' => 1,
        'platform.systems.users' => 1,
        'platform.systems.attachment' => 1,
    ];

    public function run(): void
    {
        // 1. Role: Administrator
        $adminRole = Role::firstOrNew(['slug' => 'administrator']);
        $adminRole->name = 'Адміністратор';
        $adminRole->permissions = self::ALL_PERMISSIONS;
        $adminRole->save();

        // 2. Role: Financier / Accountant
        $financierRole = Role::firstOrNew(['slug' => 'financier']);
        $financierRole->name = 'Фінансист / Бухгалтер';
        $financierRole->permissions = [
            'platform.index' => 1,
            'platform.finance.dashboard' => 1,
            'platform.finance.transactions' => 1,
            'platform.finance.bills' => 1,
            'platform.finance.invoices' => 1,
            'platform.finance.acts' => 1,
            'platform.finance.categories' => 1,
            'platform.crypto.binance' => 1,
            'platform.analytic.finance' => 1,
            'platform.fop.analytic' => 1,
            'platform.fop.profile' => 1,
            'platform.fop.tax' => 1,
            'platform.fop.ledger' => 1,
            'platform.customers' => 1,
            'platform.setting.budget-shares' => 1,
            'platform.systems.attachment' => 1,
            'platform.setting' => 0,
            'platform.setting.taxes' => 0,
            'platform.systems.roles' => 0,
            'platform.systems.users' => 0,
        ];
        $financierRole->save();

        // 3. Role: Partner / Family Member
        $partnerRole = Role::firstOrNew(['slug' => 'partner']);
        $partnerRole->name = 'Учасник бюджету (Партнер)';
        $partnerRole->permissions = [
            'platform.index' => 1,
            'platform.finance.dashboard' => 1,
            'platform.finance.transactions' => 1,
            'platform.finance.bills' => 1,
            'platform.finance.categories' => 1,
            'platform.analytic.finance' => 1,
            'platform.setting.budget-shares' => 1,
            'platform.systems.attachment' => 1,
            'platform.finance.invoices' => 0,
            'platform.finance.acts' => 0,
            'platform.crypto.binance' => 0,
            'platform.fop.analytic' => 0,
            'platform.fop.profile' => 0,
            'platform.fop.tax' => 0,
            'platform.fop.ledger' => 0,
            'platform.customers' => 0,
            'platform.setting' => 0,
            'platform.setting.taxes' => 0,
            'platform.systems.roles' => 0,
            'platform.systems.users' => 0,
        ];
        $partnerRole->save();

        // 4. Role: Viewer (Read-only)
        $viewerRole = Role::firstOrNew(['slug' => 'viewer']);
        $viewerRole->name = 'Спостерігач (Тільки перегляд)';
        $viewerRole->permissions = [
            'platform.index' => 1,
            'platform.finance.dashboard' => 1,
            'platform.analytic.finance' => 1,
            'platform.finance.transactions' => 0,
            'platform.finance.bills' => 0,
            'platform.finance.invoices' => 0,
            'platform.finance.acts' => 0,
            'platform.finance.categories' => 0,
            'platform.crypto.binance' => 0,
            'platform.fop.analytic' => 0,
            'platform.fop.profile' => 0,
            'platform.fop.tax' => 0,
            'platform.fop.ledger' => 0,
            'platform.customers' => 0,
            'platform.setting.budget-shares' => 0,
            'platform.setting' => 0,
            'platform.setting.taxes' => 0,
            'platform.systems.roles' => 0,
            'platform.systems.users' => 0,
            'platform.systems.attachment' => 0,
        ];
        $viewerRole->save();

        // 5. Ensure admin user (id: 1) has administrator role and all permissions
        $adminUser = User::find(1);
        if ($adminUser) {
            $adminUser->permissions = self::ALL_PERMISSIONS;
            $adminUser->save();
            if (!$adminUser->roles->contains($adminRole->id)) {
                $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
            }
        }

        // 6. Ensure admin2 user (id: 9) has partner role if unassigned
        $admin2 = User::find(9);
        if ($admin2 && $admin2->roles()->count() === 0) {
            $admin2->roles()->attach($partnerRole->id);
            $admin2->permissions = $partnerRole->permissions;
            $admin2->save();
        }
    }
}
