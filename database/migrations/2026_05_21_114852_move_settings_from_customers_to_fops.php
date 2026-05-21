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
            $table->foreignId('fop_id')->nullable()->constrained('fops')->nullOnDelete();
        });

        Schema::table('fops', function (Blueprint $table) {
            $table->foreignId('transaction_category_id')->nullable()->constrained('finance_transaction_categories')->nullOnDelete();
            $table->string('tax_status')->nullable();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fops', function (Blueprint $table) {
            $table->dropForeign(['transaction_category_id']);
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn(['transaction_category_id', 'tax_status', 'tax_rate_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['fop_id']);
            $table->dropColumn('fop_id');
        });
    }
};
