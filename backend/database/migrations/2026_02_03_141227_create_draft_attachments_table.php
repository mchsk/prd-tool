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
        Schema::create('draft_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prd_id');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('size_bytes');
            $table->text('extracted_text')->nullable();
            $table->enum('status', ['processing', 'ready', 'failed'])->default('processing');
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->index(['prd_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_attachments');
    }
};
