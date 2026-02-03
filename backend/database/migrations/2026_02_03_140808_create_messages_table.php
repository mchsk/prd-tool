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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prd_id');
            $table->enum('role', ['user', 'assistant'])->index();
            $table->text('content');
            $table->text('prd_update_suggestion')->nullable();
            $table->boolean('update_applied')->default(false);
            $table->integer('token_count')->default(0);
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->index(['prd_id', 'created_at']);
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->string('filename');
            $table->string('mime_type');
            $table->integer('size_bytes');
            $table->text('extracted_text')->nullable();
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
    }
};
