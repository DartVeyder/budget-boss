<?php

namespace Tests\Unit;

use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Services\Currency\Currency;
use App\Services\Finance\Transaction\TransactionsService;
use Tests\TestCase;

class FinanceLogicTest extends TestCase
{
    public function test_finance_transaction_currency_amount_accessor_returns_positive_float(): void
    {
        $transaction = new FinanceTransaction();
        $transaction->currency_amount = -150.75;
        $this->assertSame(150.75, $transaction->currency_amount);

        $transaction->currency_amount = 200.00;
        $this->assertSame(200.00, $transaction->currency_amount);
    }

    public function test_currency_parse_exchange_rates_handles_errors_gracefully(): void
    {
        $rates = Currency::parseExchangeRates();
        $this->assertIsArray($rates);
    }

    public function test_calculate_taxes_handles_scalar_and_null_rate_ids_without_type_error(): void
    {
        $service = new \App\Services\Finance\Transaction\TransactionIncomeService();

        // Null rate IDs
        $resNull = $service->calculateTaxes(1000.0, 'before_taxes', null);
        $this->assertSame(0, $resNull['total']);
        $this->assertEmpty($resNull['details']);

        // Scalar rate ID (e.g. 5) when no record in test DB
        $resScalar = $service->calculateTaxes(1000.0, 'before_taxes', 99999);
        $this->assertSame(0, $resScalar['total']);
        $this->assertEmpty($resScalar['details']);

        // Status without_taxes
        $resWithout = $service->calculateTaxes(1000.0, 'without_taxes', [1, 2]);
        $this->assertSame(0, $resWithout['total']);
        $this->assertEmpty($resWithout['details']);
    }

    public function test_update_status_invoice_handles_null_and_missing_id(): void
    {
        $service = new TransactionsService();

        // Null invoice id - should execute without error
        $service->updateStatusInvoice(null);
        $this->assertTrue(true);

        // Non-existent invoice id - should exit early without error
        $service->updateStatusInvoice(999999);
        $this->assertTrue(true);
    }
}
