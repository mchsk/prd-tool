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
        Schema::create('prds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('team_id')->nullable();
            $table->string('title')->default('Untitled PRD');
            $table->string('file_path');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->integer('estimated_tokens')->default(0);
            $table->uuid('created_from_template_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'deleted_at']);
            $table->index('team_id');
            $table->index('status');
        });

        // Add foreign key for last_prd_id in users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('last_prd_id')->references('id')->on('prds')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_prd_id']);
        });

        Schema::dropIfExists('prds');
    }
};
