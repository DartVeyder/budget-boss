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
            $table->string('tax_group', 50)->nullable()->after('phone');
            $table->boolean('is_single_tax')->default(true)->after('tax_group');
            $table->boolean('is_vat_payer')->default(false)->after('is_single_tax');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('tax_group', 50)->nullable()->after('address');
            $table->boolean('is_single_tax')->default(true)->after('tax_group');
            $table->boolean('is_vat_payer')->default(false)->after('is_single_tax');
        });

        Schema::table('acts', function (Blueprint $table) {
            $table->string('customer_tax_group', 50)->nullable()->after('customer_phone');
            $table->boolean('customer_is_single_tax')->nullable()->after('customer_tax_group');
            $table->boolean('customer_is_vat_payer')->nullable()->after('customer_is_single_tax');
            $table->string('customer_tax_info')->nullable()->after('customer_is_vat_payer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acts', function (Blueprint $table) {
            $table->dropColumn(['customer_tax_group', 'customer_is_single_tax', 'customer_is_vat_payer', 'customer_tax_info']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tax_group', 'is_single_tax', 'is_vat_payer']);
        });

        Schema::table('customer_counterparties', function (Blueprint $table) {
            $table->dropColumn(['tax_group', 'is_single_tax', 'is_vat_payer']);
        });
    }
};
