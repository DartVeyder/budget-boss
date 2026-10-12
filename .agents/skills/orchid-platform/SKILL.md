---
name: orchid-platform
description: >-
  Expert guidance for developing admin panels and interfaces using Orchid Platform (v14+) in Laravel.
  Use this skill when creating or modifying Orchid Screens, Layouts (Rows, Tables, Metrics, Listeners,
  Modals, Columns, Tabs), Form Fields, Presenters, Filters, PlatformProvider menus, permissions,
  and async screen methods.
---

# Orchid Platform Development Guide

Orchid Platform is a Rapid Application Development (RAD) admin panel for Laravel. It separates business logic from presentation using **Screens**, **Layouts**, and **Fields**.

---

## 1. Core Architecture

```
User Request ──> Route (platform.*) ──> Screen
                                          ├── query()        (Loads data into Repository)
                                          ├── commandBar()   (Action buttons / links / modals)
                                          └── layout()       (Visual representation)
                                                ├── Rows / Columns / Tabs
                                                ├── Tables (TD columns, filters, sorts)
                                                ├── Metrics (KPI cards)
                                                ├── Listeners (Dynamic reactive fields)
                                                └── Modals (Async dialogs)
```

---

## 2. Screen Anatomy

A Screen controls the page state, data loading, permissions, and actions.

```php
declare(strict_types=1);

namespace App\Orchid\Screens\Finance;

use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TransactionListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        return [
            'transactions' => FinanceTransaction::with(['customer', 'category'])
                ->filters()
                ->defaultSort('id', 'desc')
                ->paginate(20),
        ];
    }

    /**
     * Screen name displayed in the header.
     */
    public function name(): ?string
    {
        return 'Фінансові транзакції';
    }

    /**
     * Screen description.
     */
    public function description(): ?string
    {
        return 'Перегляд та управління доходами і витратами';
    }

    /**
     * Permissions required to access this screen.
     */
    public function permission(): ?iterable
    {
        return [
            'platform.finance.transactions',
        ];
    }

    /**
     * Action buttons in the top command bar.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Додати транзакцію')
                ->icon('bs.plus-circle')
                ->type(Color::PRIMARY)
                ->route('platform.finance.transactions.create'),
        ];
    }

    /**
     * Screen layout definition.
     */
    public function layout(): iterable
    {
        return [
            TransactionListTable::class,
        ];
    }

    /**
     * Screen action / handler method.
     */
    public function remove(Request $request): void
    {
        $transaction = FinanceTransaction::findOrFail($request->input('id'));
        $transaction->delete();

        Toast::info('Транзакцію успішно видалено.');
    }
}
```

---

## 3. Layouts & Visual Components

### 3.1 Rows Layout (`Layout::rows`)
Used for input forms:
```php
Layout::rows([
    Input::make('transaction.amount')
        ->title('Сума')
        ->type('number')
        ->step('0.01')
        ->required()
        ->placeholder('0.00')
        ->help('Вкажіть точну суму транзакції'),

    Relation::make('transaction.customer_id')
        ->title('Контрагент / Клієнт')
        ->fromModel(Customer::class, 'name')
        ->searchColumns('name', 'tax_number')
        ->chunk(20)
        ->empty('Оберіть клієнта'),

    DateTimer::make('transaction.transacted_at')
        ->title('Дата проведення')
        ->format('Y-m-d H:i:s')
        ->allowInput(),
])
```

### 3.2 Table Layout (`Layout::table` or `Table` class)
Create dedicated Table classes extending `Orchid\Screen\Layouts\Table`:
```php
namespace App\Orchid\Layouts\Finance;

use App\Models\FinanceTransaction;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class TransactionListTable extends Table
{
    protected $target = 'transactions';

    protected function columns(): iterable
    {
        return [
            TD::make('id', '#')
                ->sort()
                ->filter(TD::FILTER_NUMERIC),

            TD::make('transacted_at', 'Дата')
                ->render(fn (FinanceTransaction $item) => $item->transacted_at?->format('d.m.Y H:i'))
                ->sort(),

            TD::make('customer.name', 'Контрагент')
                ->filter(TD::FILTER_TEXT),

            TD::make('amount', 'Сума')
                ->render(fn (FinanceTransaction $item) => number_format((float)$item->amount, 2, '.', ' ') . ' ₴')
                ->sort(),

            TD::make('Actions', 'Дії')
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (FinanceTransaction $item) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('Редагувати')
                            ->icon('bs.pencil')
                            ->route('platform.finance.transactions.edit', $item),
                    ])),
        ];
    }
}
```

### 3.3 Dynamic Listener Layout (`Layouts\Listener`)
Essential for reactive forms when fields depend on other field inputs (e.g., selecting customer auto-fills accounts/taxes):
```php
namespace App\Orchid\Layouts\Finance;

use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;
use Illuminate\Http\Request;

class TransactionIncomeListener extends Listener
{
    // Target field names to watch for changes
    protected $targets = [
        'transaction.customer_id',
    ];

    // Method on the Screen called via async AJAX
    protected $asyncMethod = 'asyncGetCustomerDefaults';

    public function handle(Repository $repository, Request $request): Repository
    {
        $customerId = $request->input('transaction.customer_id');
        $customer = $customerId ? Customer::find($customerId) : null;

        $transaction = $repository->get('transaction', []);
        $transaction['customer_id'] = $customerId;
        if ($customer?->default_bill_id) {
            $transaction['finance_bill_id'] = $customer->default_bill_id;
        }

        return $repository->set('transaction', $transaction);
    }

    protected function layouts(): iterable
    {
        return [
            Layout::rows([
                // form fields
            ]),
        ];
    }
}
```

### 3.4 Modal Layout (`Layout::modal`)
Async modals for fast editing without reloading the entire page:
```php
Layout::modal('asyncEditCategoryModal', [
    Layout::rows([
        Input::make('category.name')->title('Назва категорії')->required(),
    ]),
])
->async('asyncGetCategory')
->title('Редагувати категорію')
->applyButton('Зберегти');
```

Screen handler for async modal:
```php
public function asyncGetCategory(FinanceTransactionCategory $category): iterable
{
    return [
        'category' => $category,
    ];
}
```

### 3.5 Metrics Layout (`Layout::metrics`)
```php
Layout::metrics([
    'Загальний дохід' => 'metrics.totalIncome',
    'Витрати за місяць' => 'metrics.monthlyExpense',
    'Чистий прибуток' => 'metrics.netProfit',
])
```

---

## 4. PlatformProvider (Navigation & Permissions)

Register screens in `app/Orchid/PlatformProvider.php`:

```php
public function menu(): array
{
    return [
        Menu::make('Фінанси')
            ->icon('bs.cash-coin')
            ->title('Бухгалтерія')
            ->list([
                Menu::make('Транзакції')
                    ->icon('bs.list-check')
                    ->route('platform.finance.transactions')
                    ->permission('platform.finance.transactions'),
                Menu::make('Рахунки')
                    ->icon('bs.wallet2')
                    ->route('platform.finance.bills'),
            ]),
    ];
}

public function permissions(): array
{
    return [
        ItemPermission::group('Фінанси')
            ->addPermission('platform.finance.transactions', 'Управління транзакціями')
            ->addPermission('platform.finance.bills', 'Управління рахунками'),
    ];
}
```

---

## 5. Routes Definition

In `routes/platform.php`:
```php
use App\Orchid\Screens\Finance\TransactionListScreen;
use App\Orchid\Screens\Finance\TransactionEditScreen;
use Tabuna\Breadcrumbs\Trail;

Route::screen('finance/transactions', TransactionListScreen::class)
    ->name('platform.finance.transactions')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Транзакції', route('platform.finance.transactions')));

Route::screen('finance/transactions/{transaction}/edit', TransactionEditScreen::class)
    ->name('platform.finance.transactions.edit')
    ->breadcrumbs(fn (Trail $trail, $transaction) => $trail
        ->parent('platform.finance.transactions')
        ->push('Редагування #' . $transaction->id));
```

---

## 6. Helpful Artisan Commands

* `php artisan orchid:screen Finance/TransactionListScreen` — Create Screen
* `php artisan orchid:table Finance/TransactionListTable` — Create Table Layout
* `php artisan orchid:layout Finance/TransactionEditRows` — Create Rows Layout
* `php artisan orchid:listener Finance/TransactionIncomeListener` — Create Listener Layout
* `php artisan orchid:filter Finance/TransactionAmountFilter` — Create Filter
