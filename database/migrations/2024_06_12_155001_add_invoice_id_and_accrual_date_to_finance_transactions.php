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
            $table->unsignedBigInteger('finance_invoice_id')->after('transaction_category_id')->nullable();
            $table->unsignedBigInteger('customer_id')->after('transaction_category_id')->nullable();
            $table->timestamp('accrual_date')->after('amount')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();

            $table->foreign('finance_invoice_id')->references('id')->on('finance_invoices')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropForeign(['finance_invoice_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn('finance_invoice_id');
            $table->dropColumn('customer_id');
            $table->dropColumn('accrual_date');
        });
    }
};
