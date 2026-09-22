<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained('signals')->onDelete('cascade');
            $table->decimal('pips_gained', 16, 2)->default(0);
            $table->decimal('max_favorable_excursion', 16, 2)->default(0);
            $table->decimal('max_adverse_excursion', 16, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_results');
    }
};
