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
        Schema::table('fops', function (Blueprint $table) {
            $table->boolean('is_esv_exempt')->default(false)->after('custom_esv')->comment('Чи звільнено ФОП від сплати ЄСВ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fops', function (Blueprint $table) {
            $table->dropColumn('is_esv_exempt');
        });
    }
};
