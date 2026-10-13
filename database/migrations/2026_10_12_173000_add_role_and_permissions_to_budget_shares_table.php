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
        Schema::table('budget_shares', function (Blueprint $table) {
            $table->string('role')->default('editor')->after('nickname');
            $table->boolean('can_create_transactions')->default(true)->after('role');
            $table->boolean('can_manage_bills')->default(true)->after('can_create_transactions');
            $table->json('permissions')->nullable()->after('can_manage_bills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_shares', function (Blueprint $table) {
            $table->dropColumn(['role', 'can_create_transactions', 'can_manage_bills', 'permissions']);
        });
    }
};
