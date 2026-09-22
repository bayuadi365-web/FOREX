<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('analysis_reports')->onDelete('cascade');
            $table->decimal('entry', 16, 6);
            $table->decimal('sl', 16, 6);
            $table->decimal('tp1', 16, 6)->nullable();
            $table->decimal('tp2', 16, 6)->nullable();
            $table->decimal('tp3', 16, 6)->nullable();
            $table->decimal('rr', 8, 2)->nullable();
            $table->string('confidence', 20)->comment('High Probability, Medium, Low');
            $table->enum('status', ['active', 'win', 'loss', 'expired'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
