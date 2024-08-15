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
        Schema::create('finance_transaction_category_mcc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_mcc_id');
            $table->unsignedBigInteger('transaction_category_id');
            $table->timestamps();

            $table->foreign('transaction_mcc_id')->references('id')->on('finance_transaction_mccs')->onDelete('cascade');
            $table->foreign('transaction_category_id')->references('id')->on('finance_transaction_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transaction_category_mcc');
    }
};
