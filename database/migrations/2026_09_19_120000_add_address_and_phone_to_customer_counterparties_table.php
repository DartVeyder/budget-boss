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
            $table->text('address')->nullable()->after('bank_name');
            $table->string('phone', 50)->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_counterparties', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone']);
        });
    }
};
