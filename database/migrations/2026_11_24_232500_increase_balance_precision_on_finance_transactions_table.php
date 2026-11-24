<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finance_transactions MODIFY balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00, MODIFY balance_bill DECIMAL(15, 2) NOT NULL DEFAULT 0.00');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finance_transactions MODIFY balance DECIMAL(8, 2) NOT NULL DEFAULT 0.00, MODIFY balance_bill DECIMAL(8, 2) NOT NULL DEFAULT 0.00');
        }
    }
};
