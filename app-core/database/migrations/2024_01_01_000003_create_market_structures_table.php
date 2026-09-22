<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pair_id')->constrained('pairs')->onDelete('cascade');
            $table->string('timeframe', 10);
            $table->enum('type', ['BOS', 'CHoCH']); // Break of Structure, Change of Character
            $table->enum('direction', ['bullish', 'bearish']);
            $table->decimal('price', 16, 6);
            $table->timestamp('detected_at');
            $table->boolean('is_swing')->default(true)->comment('true = major swing, false = internal minor');
            $table->timestamps();

            $table->index(['pair_id', 'timeframe', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_structures');
    }
};
