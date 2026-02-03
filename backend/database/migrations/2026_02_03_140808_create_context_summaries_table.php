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
        Schema::create('context_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prd_id');
            $table->text('summary');
            $table->integer('messages_summarized');
            $table->uuid('last_message_id');
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->foreign('last_message_id')->references('id')->on('messages')->cascadeOnDelete();
            $table->index(['prd_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('context_summaries');
    }
};
