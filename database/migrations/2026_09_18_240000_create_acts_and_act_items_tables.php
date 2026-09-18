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
        Schema::create('acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fop_id')->nullable()->constrained('fops')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('counterparty_id')->nullable()->constrained('customer_counterparties')->nullOnDelete();
            $table->foreignId('finance_invoice_id')->nullable()->constrained('finance_invoices')->nullOnDelete();
            $table->foreignId('finance_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();

            $table->string('act_number');
            $table->date('act_date');
            $table->string('contract_number')->nullable();
            $table->date('contract_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('currency_code', 10)->default('980');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('act_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('act_id')->constrained('acts')->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 50)->default('послуга');
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('act_items');
        Schema::dropIfExists('acts');
    }
};
