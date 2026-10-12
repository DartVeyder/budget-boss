---
name: laravel
description: >-
  Expert guidance for developing scalable, high-performance Laravel 10+ applications.
  Use this skill when working with Eloquent models, relationships, migrations, FormRequests,
  controllers, service/action classes, database transactions, queues, jobs, seeders, and tests.
---

# Laravel 10+ Development Guide

Best practices and patterns for developing clean, reliable, and performant Laravel applications.

---

## 1. Architecture & Layered Design

Keep controllers and Orchid screens thin by encapsulating business logic in dedicated **Action** or **Service** classes.

```
Request ──> FormRequest (Validation)
                 │
                 ▼
         Controller / Screen
                 │
                 ▼
          Action / Service  <─── Database Transactions (DB::transaction)
                 │
                 ▼
          Eloquent Models & Events / Jobs
```

---

## 2. Eloquent ORM & Models

### 2.1 Clean Model Setup (Modern Attributes & Casting)
```php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinanceTransaction extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'customer_id',
        'category_id',
        'finance_bill_id',
        'amount',
        'currency',
        'transacted_at',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceTransactionCategory::class, 'category_id');
    }

    // Modern Attribute Accessor / Mutator (Laravel 9/10+)
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format((float) $this->amount, 2, '.', ' ') . ' ' . $this->currency,
        );
    }

    // Local Query Scope
    public function scopeIncome($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
```

### 2.2 Preventing N+1 Query Problems
Always eager-load relations using `with()` or `loadMissing()`:
```php
// Good:
$transactions = FinanceTransaction::with(['customer', 'category'])->get();

// In AppServiceProvider boot():
Model::preventLazyLoading(! app()->isProduction());
```

---

## 3. Database Transactions & Services

Always wrap critical multi-step operations (e.g. creating transaction + updating account balances) in `DB::transaction()`:

```php
declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use Illuminate\Support\Facades\DB;

class CreateTransactionAction
{
    public function execute(array $data): FinanceTransaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = FinanceTransaction::create($data);

            // Update account balance
            $bill = FinanceBill::lockForUpdate()->findOrFail($transaction->finance_bill_id);
            $bill->balance += $transaction->amount;
            $bill->save();

            return $transaction;
        });
    }
}
```

---

## 4. Migrations & Schema Best Practices

```php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_transaction_categories')->nullOnDelete();
            $table->foreignId('finance_bill_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('UAH');
            $table->timestamp('transacted_at')->index();
            $table->text('description')->nullable();
            
            $table->timestamps();

            // Composite index for fast filtering
            $table->index(['finance_bill_id', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
```

---

## 5. Form Requests & Validation

```php
declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'not_in:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'finance_bill_id' => ['required', 'exists:finance_bills,id'],
            'transacted_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

---

## 6. Jobs, Queues & Async Processing

```php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\FinanceTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateTaxesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $customerId,
        public readonly int $year,
    ) {}

    public function handle(): void
    {
        // tax calculation logic
    }
}
```

---

## 7. Testing Practices

Write feature tests covering critical workflows:
```php
namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\FinanceBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_income_transaction(): void
    {
        $user = User::factory()->create();
        $bill = FinanceBill::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($user)->post(route('platform.finance.transactions.store'), [
            'amount' => 500,
            'finance_bill_id' => $bill->id,
            'transacted_at' => now()->toDateTimeString(),
        ]);

        $this->assertDatabaseHas('finance_transactions', [
            'finance_bill_id' => $bill->id,
            'amount' => 500,
        ]);
    }
}
```

---

## 8. Essential Artisan Commands

* `php artisan make:model FinanceTransaction -mfs` (Model with migration, factory, seeder)
* `php artisan make:request Finance/StoreTransactionRequest`
* `php artisan make:job RecalculateTaxesJob`
* `php artisan migrate:status` / `php artisan migrate`
* `php artisan optimize:clear` (Clear config, route, view caches)
* `php artisan test` (Run test suite)
