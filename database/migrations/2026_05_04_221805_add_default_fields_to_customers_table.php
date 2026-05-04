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
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('finance_bill_id')->nullable()->after('email');
            $table->string('tax_status')->nullable()->after('finance_bill_id');
            $table->unsignedBigInteger('tax_rate_id')->nullable()->after('tax_status');

            $table->foreign('finance_bill_id')->references('id')->on('finance_bills')->onDelete('set null');
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['finance_bill_id']);
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn(['finance_bill_id', 'tax_status', 'tax_rate_id']);
        });
    }
};
