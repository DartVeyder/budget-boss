<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('finance_payment_methods')->insert([
            ['name' => 'Готівка'],
            ['name' => 'Монобанк'],
            ['name' => 'Приватбанк'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_payment_methods');
    }
};
