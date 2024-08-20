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
        Schema::create('finance_binance_coin_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('binance_coin_id');
            $table->string('ticker_symbol');
            $table->double('quantity',20,8)->default(0);
            $table->decimal('amount',20,8)->default(0);
            $table->decimal('price',20,8)->default(0);
            $table->timestamps();

            $table->foreign('binance_coin_id')->references('id')->on('finance_binance_coins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_binance_coin_histories');
    }
};
