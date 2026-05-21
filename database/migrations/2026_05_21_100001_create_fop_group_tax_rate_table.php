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
        Schema::create('fop_group_tax_rate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fop_group_id')->constrained('fop_groups')->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->constrained('tax_rates')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['fop_group_id', 'tax_rate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fop_group_tax_rate');
    }
};
