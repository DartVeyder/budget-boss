---
name: unit-testing
description: >-
  Expert guidance for writing and running Unit and Feature tests in PHP and Laravel applications
  using PHPUnit and Pest. Use this skill when creating test cases, testing Eloquent models,
  service classes, Orchid screens, database transactions, mocks, factories, and assertion patterns.
---

# PHP & Laravel Unit Testing Guide

Comprehensive best practices, patterns, and cheatsheet for writing reliable, maintainable, and high-speed tests in Laravel 10+ and modern PHP 8.1+.

---

## 1. Test Architecture & Structure

Laravel separates tests into two primary directories:

```
tests/
├── Unit/               # Fast, isolated tests (services, calculations, pure logic)
│   ├── Services/       # Testing domain and action services
│   └── Helpers/        # Testing standalone utilities
├── Feature/            # Integration tests (HTTP endpoints, Screens, Database, Auth)
│   ├── Finance/        # Testing transactions, bills, categories
│   ├── Orchid/         # Testing admin screens, permissions, layouts
│   └── Api/            # Testing API routes and webhook integrations
├── TestCase.php        # Base Laravel TestCase (boots application container)
└── CreatesApplication.php
```

### 1.1 Unit vs Feature: When to Use Which
| Aspect | Unit Tests (`tests/Unit`) | Feature Tests (`tests/Feature`) |
| :--- | :--- | :--- |
| **Scope** | Single class, pure calculation, method in isolation | Full workflow, DB queries, HTTP requests, Orchid Screens |
| **Database** | Usually no DB (mocked or in-memory) | Uses `RefreshDatabase` or `DatabaseTransactions` |
| **Speed** | Extremely fast (milliseconds) | Slightly slower (full Laravel container boot) |
| **Extends** | `PHPUnit\Framework\TestCase` or `Tests\TestCase` | Always `Tests\TestCase` |

---

## 2. Modern PHPUnit 10+ Standards (PHP 8.1+)

Always adhere to clean, modern PHP standards in test files:

```php
declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxCalculationTest extends TestCase
{
    #[Test]
    public function it_calculates_single_tax_correctly(): void
    {
        // 1. Arrange
        $amount = 100_000.00;
        $ratePercent = 5.0;

        // 2. Act
        $tax = ($amount * $ratePercent) / 100;

        // 3. Assert
        $this->assertSame(5000.00, $tax);
    }

    #[Test]
    #[DataProvider('taxRatesProvider')]
    public function it_calculates_various_tax_rates(float $amount, float $rate, float $expected): void
    {
        $tax = round(($amount * $rate) / 100, 2);
        $this->assertEquals($expected, $tax);
    }

    public static function taxRatesProvider(): array
    {
        return [
            'single tax 5%'   => [10_000.00, 5.0, 500.00],
            'military tax 1%' => [10_000.00, 1.0, 100.00],
            'zero tax'        => [10_000.00, 0.0, 0.00],
        ];
    }
}
```

---

## 3. Testing Eloquent Models & Scopes

### 3.1 Testing Scopes & Relations
```php
declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\FinanceBill;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinanceBillTest extends TestCase
{
    use DatabaseTransactions; // Rolls back after test, preserving existing DB data

    public function test_scope_user_returns_only_bills_belonging_to_current_or_shared_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $bill1 = FinanceBill::create(['user_id' => $user1->id, 'name' => 'Bill 1', 'finance_currency_id' => 1]);
        $bill2 = FinanceBill::create(['user_id' => $user2->id, 'name' => 'Bill 2', 'finance_currency_id' => 1]);

        $this->actingAs($user1);

        $visibleBills = FinanceBill::user()->pluck('id');

        $this->assertTrue($visibleBills->contains($bill1->id));
        $this->assertFalse($visibleBills->contains($bill2->id));
    }
}
```

---

## 4. Testing Business Services (Transactions & Budgets)

Test services with explicit inputs and verify expected state:

```php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\BudgetShare;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BudgetShareTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_get_shared_user_ids_returns_self_and_partner_bidirectionally(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Initially individual
        $this->assertSame([$userA->id], $userA->getSharedUserIds());
        $this->assertFalse($userA->isSharingBudget());

        // Connect userA and userB
        BudgetShare::create([
            'user_id' => $userA->id,
            'partner_id' => $userB->id,
            'nickname' => 'Партнер',
        ]);

        $this->assertTrue($userA->isSharingBudget());
        $this->assertTrue($userB->isSharingBudget());

        $this->assertEqualsCanonicalizing([$userA->id, $userB->id], $userA->getSharedUserIds());
        $this->assertEqualsCanonicalizing([$userA->id, $userB->id], $userB->getSharedUserIds());
    }
}
```

---

## 5. Testing Orchid Platform Screens & Actions

When testing Orchid screens:
1. Authenticate with an authorized user using `$this->actingAs($user)`.
2. Test that the screen route returns `200 OK`.
3. Test screen POST actions (e.g., save, delete).

```php
declare(strict_types=1);

namespace Tests\Feature\Orchid;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BudgetShareScreenTest extends TestCase
{
    use DatabaseTransactions;

    public function test_screen_is_accessible_by_authenticated_user(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get(route('platform.setting.budget-shares'));

        $response->assertOk();
        $response->assertSee('Спільний бюджет');
    }

    public function test_user_can_link_partner_via_screen_method(): void
    {
        $user = User::factory()->create();
        $partner = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('platform.setting.budget-shares', ['method' => 'linkUser']),
            [
                'partner_id' => $partner->id,
                'nickname' => 'Дружина',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_shares', [
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'nickname' => 'Дружина',
        ]);
    }
}
```

---

## 6. Mocking External Services & APIs

Always fake external network requests to prevent flaky tests and keep suites fast:

```php
use Illuminate\Support\Facades\Http;

public function test_monobank_statement_sync(): void
{
    Http::fake([
        'api.monobank.ua/*' => Http::response([
            [
                'id' => 'mono_tx_123',
                'time' => 1715000000,
                'description' => 'Кав\'ярня',
                'mcc' => 5814,
                'amount' => -12000, // 120.00 UAH
                'balance' => 4500000,
            ]
        ], 200),
    ]);

    // Call Monobank service...
    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.monobank.ua'));
}
```

---

## 7. Working with Time (Freeze Time in Tests)

Prevent time-based test failures (end-of-month, midnight):

```php
use Carbon\Carbon;

public function test_monthly_filter_with_frozen_time(): void
{
    Carbon::setTestNow(Carbon::create(2026, 10, 15, 12, 0, 0));

    // Execute logic that depends on now()

    Carbon::setTestNow(); // Always reset time at end of test
}
```

---

## 8. CLI Commands for Running Tests

```bash
# Run all tests
php artisan test

# Run tests in parallel
php artisan test --parallel

# Run only Unit testsuite
php artisan test --testsuite=Unit

# Run specific test class
php artisan test tests/Unit/BudgetShareTest.php

# Run tests matching specific filter
php artisan test --filter=it_calculates_single_tax

# Stop on first failure
php artisan test --stop-on-failure
```

---

## 9. Testing Best Practices Checklist

- [ ] **Arrange-Act-Assert**: Structure every test clearly into three phases.
- [ ] **Single Responsibility**: Each test should assert one specific behavior or requirement.
- [ ] **Descriptive Naming**: Use `test_` prefix or `#[Test]` with names explaining expected behavior (e.g., `test_user_cannot_transfer_money_to_different_currency_account`).
- [ ] **Preserve Existing DB**: Use `DatabaseTransactions` on local development databases so test data is rolled back cleanly.
- [ ] **Deterministic Results**: Freeze time with `Carbon::setTestNow()` for any date calculations.
- [ ] **Mock External APIs**: Never hit real third-party APIs (Monobank, Binance, banks) in test suites.
