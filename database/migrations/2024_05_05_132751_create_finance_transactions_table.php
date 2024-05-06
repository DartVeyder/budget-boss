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
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('finance_transaction_category_id')->nullable();
            $table->unsignedBigInteger('finance_transaction_type_id');
            $table->unsignedBigInteger('finance_payment_method_id')->nullable();
            $table->unsignedBigInteger('finance_currency_id');
            $table->unsignedBigInteger('finance_source_id')->nullable();
            $table->decimal('amount',8,2);
            $table->date('expected_arrival_date');
            $table->decimal('balance',8,2);
            $table->text('description')->nullable();
            $table->softDeletes();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('finance_transaction_category_id')->references('id')->on('finance_transaction_categories')->onDelete('set null');
            $table->foreign('finance_transaction_type_id')->references('id')->on('finance_transaction_types');
            $table->foreign('finance_payment_method_id')->references('id')->on('finance_payment_methods')->onDelete('set null');
            $table->foreign('finance_currency_id')->references('id')->on('finance_currencies');
            $table->foreign('finance_source_id')->references('id')->on('finance_sources')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
