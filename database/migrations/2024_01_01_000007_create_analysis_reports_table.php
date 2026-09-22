<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pair_id')->constrained('pairs')->onDelete('cascade');
            $table->enum('bias', ['bullish', 'bearish', 'neutral']);
            $table->integer('score')->comment('0-100 score from Confluence Engine');
            $table->json('primary_scenario')->nullable();
            $table->json('alt_scenario')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['pair_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_reports');
    }
};
