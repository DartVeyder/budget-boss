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
            $table->unsignedBigInteger('finance_currency_id')->after('user_id');
            $table->foreign('finance_currency_id')->references('id')->on('finance_currencies');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_bills', function (Blueprint $table) {
            $table->dropForeign(['finance_currency_id']);
            $table->dropColumn('finance_currency_id');
        });
    }
};
