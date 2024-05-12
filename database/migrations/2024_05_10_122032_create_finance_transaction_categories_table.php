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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('transaction_type_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->timestamps();

            $table->foreign('transaction_type_id')->references('id')->on('finance_transaction_types')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        DB::table('finance_transaction_categories')->insert([
            ['name' => 'Зарплата', 'transaction_type_id' => 2, 'user_id'=> 1],
            ['name' => 'Фріланс', 'transaction_type_id' =>2, 'user_id'=> 1],
            ['name' => 'Інше', 'transaction_type_id' => 2, 'user_id'=> 1],
            ['name' => 'Переказ', 'transaction_type_id' => 2, 'user_id'=> 1],
            ['name' => 'Їжа', 'transaction_type_id' => 1, 'user_id'=> 1],
            ['name' => 'Житло', 'transaction_type_id' => 1, 'user_id'=> 1],
            ['name' => 'Транспорт', 'transaction_type_id' => 1, 'user_id'=> 1],
            ['name' => 'Розваги', 'transaction_type_id' => 1, 'user_id'=> 1],
            ['name' => 'Комунальні послуги', 'transaction_type_id' => 1, 'user_id'=> 1],
            ['name' => 'Подарунки', 'transaction_type_id' => 1, 'user_id'=> 1],
            ['name' => 'Інше', 'transaction_type_id' => 1, 'user_id'=> 1],
            ['name' => 'Переказ', 'transaction_type_id' => 1, 'user_id'=> 1],

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
