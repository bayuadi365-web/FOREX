<?php

namespace App\Services\Smc;

use App\Models\Candle;
use Illuminate\Support\Collection;

class PremiumDiscountEngine
{
    /**
     * Hitung level fibonacci (Premium/Discount/OTE) dari swing range terakhir
     */
    public function getZones(float $swingHigh, float $swingLow)
    {
        $range = $swingHigh - $swingLow;
        
        return [
            'equilibrium' => $swingHigh - ($range * 0.5),
            'ote_upper' => $swingHigh - ($range * 0.62),
            'ote_lower' => $swingHigh - ($range * 0.79),
            'is_premium' => function ($price) use ($swingHigh, $range) {
                return $price >= ($swingHigh - ($range * 0.5));
            },
            'is_discount' => function ($price) use ($swingHigh, $range) {
                return $price < ($swingHigh - ($range * 0.5));
            }
        ];
    }
}
