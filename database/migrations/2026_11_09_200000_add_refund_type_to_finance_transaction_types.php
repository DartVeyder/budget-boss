<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure type 5 "Скасування" exists in finance_transaction_types
        if (!DB::table('finance_transaction_types')->where('id', 5)->exists()) {
            DB::table('finance_transaction_types')->insert([
                'id' => 5,
                'name' => 'Скасування',
                'slug' => 'skasuvannya',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Migrate existing cancellation / refund transactions to type 5
        DB::table('finance_transactions')
            ->where('transaction_type_id', 2) // currently classified as Income
            ->where(function ($q) {
                $q->where('source_name', 'like', '%скасуван%')
                  ->orWhere('source_name', 'like', '%повернен%')
                  ->orWhere('source_name', 'like', '%refund%')
                  ->orWhere('source_name', 'like', '%reversal%')
                  ->orWhere('source_name', 'like', '%відмін%')
                  ->orWhere('comment', 'like', '%скасуван%')
                  ->orWhere('comment', 'like', '%повернен%')
                  ->orWhere('comment', 'like', '%refund%')
                  ->orWhere('comment', 'like', '%reversal%')
                  ->orWhere('comment', 'like', '%відмін%');
            })
            ->update([
                'transaction_type_id' => 5,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('finance_transactions')
            ->where('transaction_type_id', 5)
            ->update(['transaction_type_id' => 2]);

        DB::table('finance_transaction_types')->where('id', 5)->delete();
    }
};

