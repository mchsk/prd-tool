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
        Schema::create('prd_collaborators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prd_id');
            $table->uuid('user_id');
            $table->enum('role', ['viewer', 'editor'])->default('viewer');
            $table->uuid('invited_by');
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['prd_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prd_collaborators');
    }
};
