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
        Schema::create('prd_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prd_id');
            $table->uuid('created_by')->nullable();
            $table->integer('version_number');
            $table->string('title');
            $table->text('content');
            $table->string('content_hash', 32);
            $table->integer('content_size');
            $table->string('change_summary')->nullable();
            $table->enum('change_source', ['manual', 'auto', 'ai'])->default('manual');
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['prd_id', 'version_number']);
            $table->index(['prd_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prd_versions');
    }
};
