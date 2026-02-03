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
        Schema::create('sme_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable(); // null for system agents
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('expertise')->nullable();
            $table->text('system_prompt');
            $table->string('icon')->nullable();
            $table->string('category')->default('general');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_system')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['is_public', 'category']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sme_agents');
    }
};
