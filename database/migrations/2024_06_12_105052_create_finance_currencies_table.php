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
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('code');
            $table->string('symbol');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        DB::table('finance_currencies')->insert(array(
            array('id' => '1','name' => 'Гривня','code' => 'UAH','symbol' => '₴','value' => '1.0000','active' => '1','created_at' => '2024-06-27 17:42:07','updated_at' => '2024-06-28 17:44:38'),
            array('id' => '2','name' => 'Долар','code' => 'USD','symbol' => '$','value' => '40.3500','active' => '1','created_at' => '2024-06-27 10:55:38','updated_at' => '2024-07-04 11:50:28'),
            array('id' => '3','name' => 'Євро','code' => 'EUR','symbol' => '€','value' => '43.1000','active' => '1','created_at' => '2024-06-28 10:55:27','updated_at' => '2024-07-02 10:52:39')
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_currencies');
    }
};
