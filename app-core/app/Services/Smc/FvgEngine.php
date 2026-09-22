<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use App\Models\FairValueGap;
use Illuminate\Support\Collection;

class FvgEngine
{
    public function analyze(Pair $pair, string $timeframe, Collection $candles)
    {
        // Butuh minimal 3 candle untuk cek FVG (C1, C2, C3)
        if ($candles->count() < 3) return;

        // Ambil 3 candle terakhir
        $c1 = $candles[$candles->count() - 3];
        $c2 = $candles[$candles->count() - 2];
        $c3 = $candles[$candles->count() - 1];

        // Bullish FVG: Low C3 > High C1
        if ($c3->low > $c1->high) {
            $gapSize = $c3->low - $c1->high;
            if ($gapSize > $pair->pip_value) { // Filter FVG yang terlalu kecil
                // Save ke database
            }
        }

        // Bearish FVG: High C3 < Low C1
        if ($c3->high < $c1->low) {
            $gapSize = $c1->low - $c3->high;
            if ($gapSize > $pair->pip_value) {
                // Save ke database
            }
        }

        // TODO: Cek FVG lama, update fill_percentage (0% -> 50% -> 100%)
    }
}
