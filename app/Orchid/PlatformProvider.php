<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
            Menu::make('Information panel')
                ->permission('platform.finance.dashboard')
                ->route('platform.main'),

            Menu::make('Transactions')
                ->permission('platform.finance.transactions')
                ->list([
                    Menu::make('Categories')
                        ->permission('platform.finance.categories')
                        ->route('platform.transactions.categories.income'),
                ])
                ->title('Finance')
                ->route('platform.transactions'),

            Menu::make('Bills')
                ->permission('platform.finance.bills')
                ->route('platform.bills'),

            Menu::make('Invoices')
                ->permission('platform.finance.invoices')
                ->route('platform.invoices'),

            Menu::make('Акти послуг')
                ->permission('platform.finance.acts')
                ->icon('bs.file-earmark-check')
                ->route('platform.acts'),

            Menu::make('Cryptocurrency')
                ->permission('platform.crypto.binance')
                ->list([
                    Menu::make('Binance')
                        ->permission('platform.crypto.binance')
                        ->route('platform.crypto.binance'),
                ]),

            Menu::make('Analytic')
                ->list([
                    Menu::make('Finance')
                        ->permission('platform.analytic.finance')
                        ->route('platform.analytic.finance'),
                    Menu::make('ФОП')
                        ->permission('platform.fop.analytic')
                        ->icon('bs.briefcase')
                        ->route('platform.fop.analytic'),
                ]),

            Menu::make('Settings')
                ->list([
                    Menu::make('General')
                        ->permission('platform.setting')
                        ->route('platform.setting'),
                    Menu::make('Спільний бюджет')
                        ->permission('platform.setting.budget-shares')
                        ->icon('bs.people-fill')
                        ->route('platform.setting.budget-shares'),
                    Menu::make('Tax rates')
                        ->permission('platform.setting.taxes')
                        ->route('platform.setting.tax-rates'),
                    Menu::make('FOP groups')
                        ->permission('platform.setting.taxes')
                        ->route('platform.setting.fop-groups'),
                ]),

            Menu::make('Мій ФОП')
                ->icon('bs.briefcase')
                ->list([
                    Menu::make('Профіль ФОП')
                        ->permission('platform.fop.profile')
                        ->route('platform.fops'),
                    Menu::make('Аналітика ФОП')
                        ->permission('platform.fop.analytic')
                        ->icon('bs.graph-up')
                        ->route('platform.fop.analytic'),
                    Menu::make('Податки та Календар')
                        ->permission('platform.fop.tax')
                        ->route('platform.fop.tax'),
                    Menu::make('Книга доходів')
                        ->permission('platform.fop.ledger')
                        ->route('platform.fop.ledger'),
                ])
                ->title('Керування'),

            Menu::make('Customers')
                ->permission('platform.customers')
                ->icon('bs.people')
                ->route('platform.customers'),

            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),

            Menu::make(__('Roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles')
                ->divider(),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group('Фінанси')
                ->addPermission('platform.finance.dashboard', 'Головна панель (Дашборд)')
                ->addPermission('platform.finance.transactions', 'Транзакції: перегляд та керування')
                ->addPermission('platform.finance.bills', 'Рахунки: перегляд та керування')
                ->addPermission('platform.finance.invoices', 'Інвойси (Рахунки-фактури)')
                ->addPermission('platform.finance.acts', 'Акти виконаних робіт')
                ->addPermission('platform.finance.categories', 'Категорії транзакцій')
                ->addPermission('platform.crypto.binance', 'Криптовалюта та Binance'),

            ItemPermission::group('Аналітика')
                ->addPermission('platform.analytic.finance', 'Фінансова аналітика')
                ->addPermission('platform.fop.analytic', 'Аналітика ФОП'),

            ItemPermission::group('Мій ФОП')
                ->addPermission('platform.fop.profile', 'Профіль ФОП')
                ->addPermission('platform.fop.tax', 'Податки та податковий календар')
                ->addPermission('platform.fop.ledger', 'Книга обліку доходів'),

            ItemPermission::group('Клієнти та контрагенти')
                ->addPermission('platform.customers', 'Клієнти та контрагенти'),

            ItemPermission::group('Спільний бюджет')
                ->addPermission('platform.setting.budget-shares', 'Керування спільним бюджетом'),

            ItemPermission::group('Налаштування')
                ->addPermission('platform.setting', 'Загальні налаштування')
                ->addPermission('platform.setting.taxes', 'Податкові ставки та групи ФОП'),

            ItemPermission::group('Система та доступ')
                ->addPermission('platform.systems.roles', 'Керування ролями')
                ->addPermission('platform.systems.users', 'Керування користувачами'),
        ];
    }
}
