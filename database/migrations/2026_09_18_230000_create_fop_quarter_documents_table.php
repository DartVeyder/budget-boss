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
        Schema::create('fop_quarter_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fop_id')->constrained('fops')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->string('document_type', 50)->default('other');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->unsignedInteger('attachment_id')->nullable();
            $table->foreign('attachment_id')->references('id')->on('attachments')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->string('original_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->index(['fop_id', 'year', 'quarter']);
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fop_quarter_documents');
    }
};
