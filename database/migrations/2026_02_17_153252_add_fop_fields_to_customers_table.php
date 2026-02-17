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
            $table->string('ipn')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('mfo')->nullable();
            $table->string('iban')->nullable();
            $table->string('edrpou')->nullable();
            $table->string('director')->nullable();
            $table->boolean('is_fop')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['ipn', 'address', 'bank_name', 'mfo', 'iban', 'edrpou', 'director', 'is_fop']);
        });
    }
};
