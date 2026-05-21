<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$transactions = \App\Models\FinanceTransaction::where('tax_amount', '>', 0)->get();
$count        = 0;
foreach ($transactions as $transaction) {
    if ($transaction->taxes()->exists())
        continue;

    $taxRate = $transaction->customer?->fop?->fopGroup?->taxRates?->first();
    if ($taxRate) {
        $transaction->taxes()->attach($taxRate->id, ['amount' => $transaction->tax_amount]);
        $count++;
    }
}
echo 'Migrated ' . $count . ' transactions.';
