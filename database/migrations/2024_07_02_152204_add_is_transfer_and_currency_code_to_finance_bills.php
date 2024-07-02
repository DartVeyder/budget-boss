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
        Schema::table('finance_bills', function (Blueprint $table) {
            $table->string('currency_code')->after('finance_currency_id');
            $table->boolean('is_transfer')->after('name')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_bills', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('is_transfer');
        });
    }
};
