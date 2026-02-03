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
        Schema::create('prd_share_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prd_id');
            $table->string('token', 64)->unique();
            $table->enum('access_level', ['view', 'comment'])->default('view');
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->uuid('created_by');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['token', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prd_share_links');
    }
};
