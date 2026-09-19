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
        Schema::table('acts', function (Blueprint $table) {
            $table->text('customer_address')->nullable()->after('contract_date');
            $table->string('customer_phone', 50)->nullable()->after('customer_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acts', function (Blueprint $table) {
            $table->dropColumn(['customer_address', 'customer_phone']);
        });
    }
};
