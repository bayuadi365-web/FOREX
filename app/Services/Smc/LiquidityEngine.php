<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use App\Models\LiquidityLevel;
use Illuminate\Support\Collection;

class LiquidityEngine
{
    protected float $pipTolerance;

    public function __construct(float $pipTolerance = 2.0)
    {
        $this->pipTolerance = $pipTolerance;
    }

    public function analyze(Pair $pair, string $timeframe, Collection $swings, Collection $candles)
    {
        $this->detectEqualHighsLows($pair, $timeframe, $swings);
        $this->detectLiquiditySweeps($pair, $timeframe, $candles);
    }

    protected function detectEqualHighsLows(Pair $pair, string $timeframe, Collection $swings)
    {
        // Pisahkan highs dan lows
        $highs = $swings->where('type', 'high')->values();
        $lows = $swings->where('type', 'low')->values();

        // Toleransi dalam decimal (pip_value * tolerance)
        $toleranceValue = $pair->pip_value * $this->pipTolerance;

        // Cek Equal Highs
        for ($i = 0; $i < $highs->count() - 1; $i++) {
            $h1 = $highs[$i]['candle']->high;
            $h2 = $highs[$i+1]['candle']->high;
            
            if (abs($h1 - $h2) <= $toleranceValue) {
                // Simpan EQH ke database jika belum tersweep
            }
        }
        
        // Cek Equal Lows
        for ($i = 0; $i < $lows->count() - 1; $i++) {
            $l1 = $lows[$i]['candle']->low;
            $l2 = $lows[$i+1]['candle']->low;
            
            if (abs($l1 - $l2) <= $toleranceValue) {
                // Simpan EQL ke database jika belum tersweep
            }
        }
    }

    protected function detectLiquiditySweeps(Pair $pair, string $timeframe, Collection $candles)
    {
        // Cari wick yang menembus liquidity level lalu close di bawah/atas level tersebut (Turtle Soup / Stop Hunt)
    }
}
