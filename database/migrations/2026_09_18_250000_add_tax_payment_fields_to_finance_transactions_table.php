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
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->string('tax_type', 50)->nullable()->after('fop_id');
            $table->unsignedTinyInteger('tax_quarter')->nullable()->after('tax_type');
            $table->unsignedSmallInteger('tax_year')->nullable()->after('tax_quarter');

            $table->index(['fop_id', 'tax_type', 'tax_year', 'tax_quarter'], 'ft_fop_tax_period_index');
        });

        // Backfill existing tax payment transactions based on comments
        $transactions = DB::table('finance_transactions')
            ->where('type', 'expenses')
            ->where(function ($q) {
                $q->where('comment', 'like', '%подат%')
                  ->orWhere('comment', 'like', '%єсв%')
                  ->orWhere('comment', 'like', '%збір%')
                  ->orWhere('comment', 'like', '%військ%');
            })
            ->get(['id', 'comment', 'created_at', 'fop_id']);

        $defaultFopId = DB::table('fops')->value('id') ?? 1;

        foreach ($transactions as $tx) {
            $comment = mb_strtolower($tx->comment ?? '');
            $taxType = null;
            $quarter = null;
            $year = null;

            // Determine tax type
            if (str_contains($comment, 'військов')) {
                $taxType = 'military_tax';
            } elseif (str_contains($comment, 'єдин')) {
                $taxType = 'single_tax';
            } elseif (str_contains($comment, 'єсв')) {
                $taxType = 'esv';
            }

            if (!$taxType) {
                continue;
            }

            // Determine quarter
            if (str_contains($comment, 'січень-березень') || str_contains($comment, '1 кв') || str_contains($comment, 'i кв')) {
                $quarter = 1;
            } elseif (str_contains($comment, 'квітень-червень') || str_contains($comment, '2 кв') || str_contains($comment, 'ii кв')) {
                $quarter = 2;
            } elseif (str_contains($comment, 'липень') || str_contains($comment, 'липень-вересень') || str_contains($comment, '3 кв') || str_contains($comment, 'iii кв')) {
                $quarter = 3;
            } elseif (str_contains($comment, 'жовтень-грудень') || str_contains($comment, '4 кв') || str_contains($comment, 'iv кв')) {
                $quarter = 4;
            }

            // Determine year
            if (preg_match('/(202\d)/', $comment, $matches)) {
                $year = (int)$matches[1];
            } elseif (!empty($tx->created_at)) {
                $year = (int)date('Y', strtotime($tx->created_at));
            }

            if ($taxType && $quarter && $year) {
                DB::table('finance_transactions')
                    ->where('id', $tx->id)
                    ->update([
                        'tax_type' => $taxType,
                        'tax_quarter' => $quarter,
                        'tax_year' => $year,
                        'fop_id' => $tx->fop_id ?: $defaultFopId,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropIndex('ft_fop_tax_period_index');
            $table->dropColumn(['tax_type', 'tax_quarter', 'tax_year']);
        });
    }
};
