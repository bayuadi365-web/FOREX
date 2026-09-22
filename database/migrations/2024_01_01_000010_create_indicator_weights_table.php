<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_weights', function (Blueprint $table) {
            $table->id();
            $table->string('indicator_key', 50)->unique();
            $table->integer('weight_value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default weights
        DB::table('indicator_weights')->insert([
            ['indicator_key' => 'htf_bias_aligned', 'weight_value' => 25, 'description' => 'HTF bias searah'],
            ['indicator_key' => 'choch_bos_confirmed', 'weight_value' => 20, 'description' => 'CHoCH/BOS terkonfirmasi'],
            ['indicator_key' => 'fresh_ob', 'weight_value' => 15, 'description' => 'Harga berada di OB fresh'],
            ['indicator_key' => 'unfilled_fvg', 'weight_value' => 10, 'description' => 'FVG belum terisi searah'],
            ['indicator_key' => 'liquidity_sweep', 'weight_value' => 15, 'description' => 'Liquidity sweep baru terjadi'],
            ['indicator_key' => 'premium_discount', 'weight_value' => 10, 'description' => 'Posisi premium/discount benar'],
            ['indicator_key' => 'in_killzone', 'weight_value' => 5, 'description' => 'Di dalam killzone'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_weights');
    }
};
