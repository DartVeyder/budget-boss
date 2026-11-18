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
        Schema::table('finance_bills', function (Blueprint $table) {
            $table->string('monobank_account_id')->nullable()->after('iban');
        });

        Schema::table('fops', function (Blueprint $table) {
            $table->string('monobank_account_id')->nullable()->after('iban');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_bills', function (Blueprint $table) {
            $table->dropColumn('monobank_account_id');
        });

        Schema::table('fops', function (Blueprint $table) {
            $table->dropColumn('monobank_account_id');
        });
    }
};

