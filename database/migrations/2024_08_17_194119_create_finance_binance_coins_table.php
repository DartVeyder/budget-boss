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
        Schema::create('finance_binance_coins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('ticker_symbol');
            $table->double('quantity',20,8)->default(0);
            $table->decimal('amount',20,8)->default(0);
            $table->decimal('price',20,8)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_binance_coins');
    }
};
