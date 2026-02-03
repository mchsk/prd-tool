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
        Schema::create('prd_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prd_id');
            $table->uuid('user_id')->nullable(); // null for anonymous via share link
            $table->string('author_name')->nullable(); // for anonymous commenters
            $table->text('content');
            $table->integer('line_number')->nullable(); // for inline comments
            $table->string('anchor_text')->nullable(); // text snippet for context
            $table->uuid('parent_id')->nullable(); // for threaded replies
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('prd_comments')->cascadeOnDelete();

            $table->index(['prd_id', 'created_at']);
            $table->index(['prd_id', 'is_resolved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prd_comments');
    }
};
