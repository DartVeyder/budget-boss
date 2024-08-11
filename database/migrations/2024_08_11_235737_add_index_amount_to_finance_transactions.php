<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->index('absolute_currency_amount');
            $table->index('currency_amount');
            $table->index('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropIndex(['amount']);
            $table->dropIndex(['currency_amount']);
            $table->dropIndex(['absolute_currency_amount']);
        });
    }
};
