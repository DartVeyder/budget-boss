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
        Schema::create('finance_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->timestamps();

        });
        DB::table('finance_transaction_types')->insert([
            ['name' => 'Витрати', 'slug' => 'vitrati' ],
            ['name' => 'Дохід', 'slug' => 'dohid' ],
            ['name' => 'Переказ', 'slug' => 'perekaz'  ],
            ['name' => 'Ревізія', 'slug' => 'reviziya'  ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transaction_types');
    }
};
