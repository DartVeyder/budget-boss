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
            $table->unsignedBigInteger('transaction_type_id');
            $table->unsignedBigInteger('transaction_category_id')->nullable();
            $table->unsignedBigInteger('finance_bill_id');
            $table->decimal('amount');
            $table->text('comment')->nullable();
            $table->string('type');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('transaction_type_id')->references('id')->on('finance_transaction_types');
            $table->foreign('finance_bill_id')->references('id')->on('finance_bills')->onDelete('cascade');
            $table->foreign('transaction_category_id')->references('id')->on('finance_transaction_categories')->onDelete('set null');
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
