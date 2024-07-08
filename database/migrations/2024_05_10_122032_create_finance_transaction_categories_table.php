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
        Schema::create('finance_transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('transaction_type_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->timestamps();

            $table->foreign('transaction_type_id')->references('id')->on('finance_transaction_types')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        DB::table('finance_transaction_categories')->insert([
            ['name' => 'Переказ'],
            ['name' => 'Ревізія'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transaction_categories');
    }
};
