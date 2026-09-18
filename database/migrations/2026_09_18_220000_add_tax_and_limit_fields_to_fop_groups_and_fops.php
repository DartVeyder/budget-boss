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
        Schema::table('fop_groups', function (Blueprint $table) {
            $table->decimal('annual_limit', 15, 2)->nullable()->after('name')->comment('Річний ліміт доходу в гривнях');
            $table->decimal('monthly_esv', 10, 2)->default(1760.00)->after('annual_limit')->comment('Базова щомісячна ставка ЄСВ');
        });

        Schema::table('fops', function (Blueprint $table) {
            $table->decimal('annual_limit', 15, 2)->nullable()->after('tax_status')->comment('Персональний ліміт доходу (перекриває групу)');
            $table->decimal('custom_esv', 10, 2)->nullable()->after('annual_limit')->comment('Персональна ставка ЄСВ');
        });

        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->foreignId('fop_id')->nullable()->after('finance_bill_id')->constrained('fops')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropForeign(['fop_id']);
            $table->dropColumn('fop_id');
        });

        Schema::table('fops', function (Blueprint $table) {
            $table->dropColumn(['annual_limit', 'custom_esv']);
        });

        Schema::table('fop_groups', function (Blueprint $table) {
            $table->dropColumn(['annual_limit', 'monthly_esv']);
        });
    }
};
