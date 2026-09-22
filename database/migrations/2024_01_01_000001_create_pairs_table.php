<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pairs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->unique();
            $table->string('base_currency', 10);
            $table->string('quote_currency', 10);
            $table->integer('pip_digit')->comment('e.g., 4 for EURUSD, 2 for USDJPY');
            $table->decimal('pip_value', 16, 8)->default(0.0001);
            $table->decimal('average_spread', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairs');
    }
};
