<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pair_id')->constrained('pairs')->onDelete('cascade');
            $table->string('timeframe', 10);
            $table->enum('direction', ['bullish', 'bearish']);
            $table->decimal('top', 16, 6);
            $table->decimal('bottom', 16, 6);
            $table->enum('status', ['fresh', 'tested', 'mitigated', 'invalidated'])->default('fresh');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('mitigated_at')->nullable();
            
            $table->index(['pair_id', 'timeframe', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_blocks');
    }
};
