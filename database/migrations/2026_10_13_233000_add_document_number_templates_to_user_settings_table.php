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
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('act_number_template')->nullable()->default('АКТ-{YYYY}/{MM}-{NUM}');
            $table->string('invoice_number_template')->nullable()->default('РАХ-{YYYY}/{MM}-{NUM}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn(['act_number_template', 'invoice_number_template']);
        });
    }
};
