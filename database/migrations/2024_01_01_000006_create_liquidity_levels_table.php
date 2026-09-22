<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidity_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pair_id')->constrained('pairs')->onDelete('cascade');
            $table->string('timeframe', 10);
            $table->string('type', 20); // EQH, EQL, AsianHigh, AsianLow
            $table->decimal('price', 16, 6);
            $table->enum('status', ['intact', 'swept'])->default('intact');
            $table->timestamp('swept_at')->nullable();
            $table->timestamps();

            $table->index(['pair_id', 'timeframe', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidity_levels');
    }
};
