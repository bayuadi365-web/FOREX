<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fair_value_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pair_id')->constrained('pairs')->onDelete('cascade');
            $table->string('timeframe', 10);
            $table->enum('direction', ['bullish', 'bearish']);
            $table->decimal('top', 16, 6);
            $table->decimal('bottom', 16, 6);
            $table->decimal('fill_percentage', 5, 2)->default(0);
            $table->enum('status', ['unfilled', 'partial', 'filled'])->default('unfilled');
            $table->timestamps();

            $table->index(['pair_id', 'timeframe', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fair_value_gaps');
    }
};
