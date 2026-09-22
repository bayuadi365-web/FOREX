<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use App\Models\MarketStructure;
use Illuminate\Support\Collection;

class MarketStructureEngine
{
    /**
     * Lookback & lookforward bars for fractal detection
     */
    protected int $fractalPeriod;

    public function __construct(int $fractalPeriod = 2)
    {
        $this->fractalPeriod = $fractalPeriod;
    }

    /**
     * Menganalisa struktur pasar berdasarkan data candle terakhir.
     */
    public function analyze(Pair $pair, string $timeframe, Collection $candles)
    {
        // 1. Deteksi Fractals (Swing High / Swing Low)
        $swings = $this->detectFractals($candles);
        
        // 2. Identifikasi BOS / CHoCH
        $this->identifyStructures($pair, $timeframe, $swings, $candles);
        
        // Return status (bullish, bearish, ranging)
        return $this->determineTrend($pair, $timeframe);
    }

    /**
     * Algoritma mendeteksi fractal (n-bar kiri dan kanan)
     */
    protected function detectFractals(Collection $candles): Collection
    {
        $swings = collect();
        $count = $candles->count();
        
        for ($i = $this->fractalPeriod; $i < $count - $this->fractalPeriod; $i++) {
            $isHigh = true;
            $isLow = true;
            
            $currentHigh = $candles[$i]->high;
            $currentLow = $candles[$i]->low;

            // Cek n-bar kiri dan kanan
            for ($j = 1; $j <= $this->fractalPeriod; $j++) {
                if ($candles[$i - $j]->high >= $currentHigh || $candles[$i + $j]->high >= $currentHigh) {
                    $isHigh = false;
                }
                if ($candles[$i - $j]->low <= $currentLow || $candles[$i + $j]->low <= $currentLow) {
                    $isLow = false;
                }
            }

            if ($isHigh) {
                $swings->push(['type' => 'high', 'candle' => $candles[$i]]);
            }
            if ($isLow) {
                $swings->push(['type' => 'low', 'candle' => $candles[$i]]);
            }
        }

        return $swings;
    }

    /**
     * Identifikasi Break of Structure (BOS) dan Change of Character (CHoCH)
     */
    protected function identifyStructures(Pair $pair, string $timeframe, Collection $swings, Collection $candles): void
    {
        // Logika kompleks identifikasi penembusan swing point.
        // Konsep dasar:
        // Jika tren bullish, tembus resistance (Swing High) = BOS Bullish.
        // Jika tren bullish, tembus support terakhir (Swing Low) = CHoCH Bearish.
        // Implementasi sesungguhnya butuh melacak state tren sebelumnya.
        
        // Pseudo-implementasi untuk struktur dasar (untuk di-save ke DB)
        // MarketStructure::updateOrCreate([...]);
    }

    public function determineTrend(Pair $pair, string $timeframe): string
    {
        // Berdasarkan BOS/CHoCH terakhir, tentukan apakah Bullish/Bearish
        $lastStructure = MarketStructure::where('pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->orderBy('detected_at', 'desc')
            ->first();
            
        if (!$lastStructure) return 'ranging';
        
        if ($lastStructure->type === 'BOS' && $lastStructure->direction === 'bullish') return 'bullish';
        if ($lastStructure->type === 'CHoCH' && $lastStructure->direction === 'bullish') return 'bullish';
        
        if ($lastStructure->type === 'BOS' && $lastStructure->direction === 'bearish') return 'bearish';
        if ($lastStructure->type === 'CHoCH' && $lastStructure->direction === 'bearish') return 'bearish';
        
        return 'ranging';
    }
}
