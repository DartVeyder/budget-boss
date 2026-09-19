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
        Schema::table('customer_counterparties', function (Blueprint $table) {
            $table->string('contract_number')->nullable()->after('phone');
            $table->date('contract_date')->nullable()->after('contract_number');
            $table->string('contract_name')->nullable()->after('contract_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_counterparties', function (Blueprint $table) {
            $table->dropColumn(['contract_number', 'contract_date', 'contract_name']);
        });
    }
};
