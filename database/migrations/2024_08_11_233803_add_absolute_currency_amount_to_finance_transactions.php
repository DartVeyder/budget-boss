<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->decimal('absolute_currency_amount', 8,2)->after('currency_amount')->default(0);
        });
        DB::table('finance_transactions')->update([
            'absolute_currency_amount' => DB::raw('ABS(currency_amount)')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
             $table->dropColumn('absolute_currency_amount');
        });

    }
};
