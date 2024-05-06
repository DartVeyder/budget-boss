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
        Schema::create('finance_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
        });

        DB::table('finance_currencies')->insert([
            ['name' => 'Гривня', 'code' => 'uah'],
            ['name' => 'Долар', 'code' => 'usd'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_currencies');
    }
};
