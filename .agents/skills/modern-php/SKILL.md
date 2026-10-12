---
name: modern-php
description: >-
  Best practices, modern standards, and features for PHP 8.1+ development.
  Use this skill when writing clean PHP code, implementing PHP 8+ features (Enums, Readonly,
  Constructor Promotion, Match expressions, Nullsafe operator, Type declarations), adhering to
  PSR-12 coding style, writing domain exceptions, using generators, and managing Composer packages.
---

# Modern PHP (8.1+) Standards & Guidelines

Guidelines for writing safe, performant, and maintainable PHP code using PHP 8.1+ capabilities.

---

## 1. Strict Typing & Type Safety

Always declare strict types at the very top of every PHP file:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Finance;
```

### Type Hints Everywhere
- Always declare parameter types, return types, and class property types.
- Use `void` when a method does not return a value.
- Use `never` for functions that throw or terminate (`exit`).
- Use union types (`int|string`) and intersection types (`Countable&Iterator`) where necessary.

```php
public function findById(int|string $id): ?Transaction
{
    // ...
}
```

---

## 2. Core PHP 8.1+ Features

### 2.1 Backed Enums
Enums replace legacy class constants with type-safe enumerated values:

```php
declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Дохід',
            self::EXPENSE => 'Витрата',
            self::TRANSFER => 'Переказ між рахунками',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INCOME => 'success',
            self::EXPENSE => 'danger',
            self::TRANSFER => 'info',
        };
    }
}
```

### 2.2 Constructor Property Promotion & Readonly
Eliminate boilerplate property assignments:

```php
declare(strict_types=1);

namespace App\DTO\Finance;

use App\Enums\TransactionType;
use DateTimeImmutable;

readonly class TransactionDTO
{
    public function __construct(
        public float $amount,
        public string $currency,
        public TransactionType $type,
        public DateTimeImmutable $transactedAt,
        public ?int $customerId = null,
        public ?string $description = null,
    ) {}
}
```

### 2.3 Match Expressions (vs switch)
Match expressions are strictly evaluated (`===`), return values, and prevent fallthrough errors:

```php
$statusBadge = match ($invoice->status) {
    'paid' => '<span class="badge bg-success">Оплачено</span>',
    'pending' => '<span class="badge bg-warning">В очікуванні</span>',
    'cancelled', 'rejected' => '<span class="badge bg-danger">Скасовано</span>',
    default => '<span class="badge bg-secondary">Невідомо</span>',
};
```

### 2.4 Nullsafe Operator (`?->`)
Prevents cumbersome `!is_null()` or `isset()` chains:

```php
// Old:
$taxNumber = $customer && $customer->fop ? $customer->fop->tax_number : null;

// Modern PHP 8.1+:
$taxNumber = $customer?->fop?->tax_number;
```

### 2.5 Arrow Functions & First-Class Callables
```php
// Arrow functions:
$activeCustomers = array_filter($customers, fn ($c) => $c->isActive);

// First-class callables:
$names = array_map(strtoupper(...), $rawNames);
```

---

## 3. Memory Optimization & Large Data Handling

When exporting or parsing large datasets (e.g. hundreds of thousands of transactions, bank statements, CSV imports), use **Generators** (`yield`) instead of loading everything into memory:

```php
public function parseBankStatement(string $filePath): \Generator
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        throw new \RuntimeException("Cannot open file {$filePath}");
    }

    try {
        // Skip header
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            yield [
                'date' => $row[0],
                'amount' => (float) $row[1],
                'description' => $row[2],
            ];
        }
    } finally {
        fclose($handle);
    }
}
```

---

## 4. Custom Domain Exceptions

Avoid generic `\Exception` in business logic. Create explicit domain exceptions:

```php
declare(strict_types=1);

namespace App\Exceptions\Finance;

use DomainException;

class InsufficientBalanceException extends DomainException
{
    public static function forBill(int $billId, float $currentBalance, float $requestedAmount): self
    {
        return new self(sprintf(
            'На рахунку #%d недостатньо коштів. Баланс: %.2f, запитано: %.2f',
            $billId,
            $currentBalance,
            $requestedAmount
        ));
    }
}
```

---

## 5. Coding Standards Checklist

- [ ] File begins with `declare(strict_types=1);`.
- [ ] Conforms to PSR-12 formatting (4 spaces, opening braces on next line for classes/methods).
- [ ] No raw SQL string interpolation (always parameterized).
- [ ] Avoid `eval()`, `extract()`, or mutable global variables.
- [ ] Enums used for statuses, types, and fixed sets.
- [ ] Type declarations present on all public and protected methods.
