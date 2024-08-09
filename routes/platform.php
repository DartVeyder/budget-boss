<?php

declare(strict_types=1);

use App\Orchid\Screens\Analytic\AnalyticScreen;
use App\Orchid\Screens\DashboardScreen;
use App\Orchid\Screens\Examples\ExampleActionsScreen;
use App\Orchid\Screens\Examples\ExampleCardsScreen;
use App\Orchid\Screens\Examples\ExampleChartsScreen;
use App\Orchid\Screens\Examples\ExampleFieldsAdvancedScreen;
use App\Orchid\Screens\Examples\ExampleFieldsScreen;
use App\Orchid\Screens\Examples\ExampleGridScreen;
use App\Orchid\Screens\Examples\ExampleLayoutsScreen;
use App\Orchid\Screens\Examples\ExampleScreen;
use App\Orchid\Screens\Examples\ExampleTextEditorsScreen;
use App\Orchid\Screens\Finance\Bill\BillScreen;
use App\Orchid\Screens\Finance\Invoice\InvoiceListScreen;
use App\Orchid\Screens\Finance\Transaction\Category\CategoryExpensesScreen;
use App\Orchid\Screens\Finance\Transaction\Category\CategoryIncomeScreen;
use App\Orchid\Screens\Finance\Transaction\Category\CategoryScreen;
use App\Orchid\Screens\Finance\Transaction\Category\TransactionCategoryListScreen;
use App\Orchid\Screens\Finance\Transaction\TransactionCardScreen;
use App\Orchid\Screens\Finance\Transaction\TransactionEditExpensesScreen;
use App\Orchid\Screens\Finance\Transaction\TransactionEditScreen;
use App\Orchid\Screens\Finance\Transaction\TransactionListScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Main
Route::screen('/main', DashboardScreen::class)
    ->name('platform.main');

//Platform > Transactions
Route::screen('transactions', TransactionListScreen::class)
    ->name('platform.transactions')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Transactions'), route('platform.transactions')));

//Platform > Transactions > edit
Route::screen('transactions/{transaction}/edit', TransactionEditScreen::class)
    ->name('platform.transactions.edit')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.transactions')
        ->push(__('Edit')));


//Platform > Transactions > Category
Route::screen('transactions/categories', CategoryScreen::class)
    ->name('platform.transactions.categories')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.transactions')
        ->push(__('Categories'), route('platform.transactions')));

//Platform > Transactions > Category > Income
Route::screen('transactions/categories/income',  CategoryIncomeScreen::class)
    ->name('platform.transactions.categories.income')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.transactions')
        ->push(__('Categories'), route('platform.transactions')));

//Platform > Transactions > Category > Expenses
Route::screen('transactions/categories/expenses', CategoryExpensesScreen::class)
    ->name('platform.transactions.categories.expenses')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.transactions')
        ->push(__('Categories'), route('platform.transactions')));

//Platform > Bills
Route::screen('/bills',  BillScreen::class)
    ->name('platform.bills')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Bills'), route('platform.bills')));

//Platform > Invoices
Route::screen('/invoices', InvoiceListScreen::class)
    ->name('platform.invoices')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Invoices'), route('platform.invoices')));


//Platform > Analytic
Route::screen('/analytic', AnalyticScreen::class)
    ->name('platform.analytic')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Analytic'), route('platform.analytic')));





// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

// Example...
Route::screen('example', ExampleScreen::class)
    ->name('platform.example')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Example Screen'));

Route::screen('/examples/form/fields', ExampleFieldsScreen::class)->name('platform.example.fields');
Route::screen('/examples/form/advanced', ExampleFieldsAdvancedScreen::class)->name('platform.example.advanced');
Route::screen('/examples/form/editors', ExampleTextEditorsScreen::class)->name('platform.example.editors');
Route::screen('/examples/form/actions', ExampleActionsScreen::class)->name('platform.example.actions');

Route::screen('/examples/layouts', ExampleLayoutsScreen::class)->name('platform.example.layouts');
Route::screen('/examples/grid', ExampleGridScreen::class)->name('platform.example.grid');
Route::screen('/examples/charts', ExampleChartsScreen::class)->name('platform.example.charts');
Route::screen('/examples/cards', ExampleCardsScreen::class)->name('platform.example.cards');

//Route::screen('idea', Idea::class, 'platform.screens.idea');
