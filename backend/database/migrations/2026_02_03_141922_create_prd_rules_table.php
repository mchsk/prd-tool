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
        Schema::create('prd_rules', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID for pivot table
            $table->uuid('prd_id');
            $table->uuid('rule_id');
            $table->integer('priority')->default(0); // Lower = higher priority
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();

            $table->unique(['prd_id', 'rule_id']);
            $table->index(['prd_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prd_rules');
    }
};
