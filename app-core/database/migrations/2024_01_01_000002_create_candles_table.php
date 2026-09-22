<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pair_id')->constrained('pairs')->onDelete('cascade');
            $table->string('timeframe', 10); // e.g., M1, M5, M15, M30, H1, H4, D1
            $table->timestamp('open_time');
            $table->decimal('open', 16, 6);
            $table->decimal('high', 16, 6);
            $table->decimal('low', 16, 6);
            $table->decimal('close', 16, 6);
            $table->bigInteger('volume')->default(0);
            $table->timestamps();

            $table->unique(['pair_id', 'timeframe', 'open_time']);
        });

        // Add table partitioning for MariaDB (partition by month)
        // Note: For simplicity in the initial migration we use native SQL
        /*
        DB::statement('
            ALTER TABLE candles 
            PARTITION BY RANGE (UNIX_TIMESTAMP(open_time)) (
                PARTITION p_2024_01 VALUES LESS THAN (UNIX_TIMESTAMP("2024-02-01 00:00:00")),
                PARTITION p_2024_02 VALUES LESS THAN (UNIX_TIMESTAMP("2024-03-01 00:00:00")),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            );
        ');
        */
    }

    public function down(): void
    {
        Schema::dropIfExists('candles');
    }
};
