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
        Schema::create('prd_sme_agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('prd_id');
            $table->uuid('sme_agent_id');
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
            $table->foreign('sme_agent_id')->references('id')->on('sme_agents')->cascadeOnDelete();

            $table->unique(['prd_id', 'sme_agent_id']);
            $table->index('prd_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prd_sme_agents');
    }
};
