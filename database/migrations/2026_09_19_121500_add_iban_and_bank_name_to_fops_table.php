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
        Schema::table('fops', function (Blueprint $table) {
            $table->string('iban', 50)->nullable()->after('address');
            $table->string('bank_name', 255)->nullable()->after('iban');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fops', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bank_name']);
        });
    }
};
