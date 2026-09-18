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
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->foreignId('fop_id')->nullable()->after('user_id')->constrained('fops')->nullOnDelete();
            $table->foreignId('counterparty_id')->nullable()->after('customer_id')->constrained('customer_counterparties')->nullOnDelete();
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->date('due_date')->nullable()->after('invoice_date');
            $table->string('contract_number')->nullable()->after('due_date');
            $table->date('contract_date')->nullable()->after('contract_number');
            $table->json('items_data')->nullable()->after('comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropForeign(['fop_id']);
            $table->dropForeign(['counterparty_id']);
            $table->dropColumn([
                'fop_id',
                'counterparty_id',
                'invoice_date',
                'due_date',
                'contract_number',
                'contract_date',
                'items_data',
            ]);
        });
    }
};
