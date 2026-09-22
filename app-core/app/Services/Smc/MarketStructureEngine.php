<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use App\Models\MarketStructure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     * Menganalisa struktur pasar berdasarkan data candle dari DB.
     * Mendeteksi swing points, identifikasi BOS/CHoCH, dan simpan ke DB.
     */
    public function analyze(Pair $pair, string $timeframe, Collection $candles): string
    {
        if ($candles->count() < 10) {
            return 'ranging';
        }

        // 1. Deteksi Fractals (Swing High / Swing Low)
        $swings = $this->detectFractals($candles);
        
        if ($swings->count() < 4) {
            return 'ranging';
        }

        // 2. Identifikasi BOS / CHoCH dan simpan ke DB
        $this->identifyStructures($pair, $timeframe, $swings);
        
        // 3. Return trend berdasarkan struktur terakhir
        return $this->determineTrend($pair, $timeframe);
    }

    /**
     * Algoritma mendeteksi fractal (n-bar kiri dan kanan)
     */
    protected function detectFractals(Collection $candles): Collection
    {
        $swings = collect();
        $values = $candles->values(); // Re-index
        $count = $values->count();
        
        for ($i = $this->fractalPeriod; $i < $count - $this->fractalPeriod; $i++) {
            $isHigh = true;
            $isLow = true;
            
            $current = $values[$i];
            $currentHigh = is_array($current) ? $current['high'] : $current->high;
            $currentLow = is_array($current) ? $current['low'] : $current->low;

            // Cek n-bar kiri dan kanan
            for ($j = 1; $j <= $this->fractalPeriod; $j++) {
                $left = $values[$i - $j];
                $right = $values[$i + $j];
                
                $leftHigh = is_array($left) ? $left['high'] : $left->high;
                $rightHigh = is_array($right) ? $right['high'] : $right->high;
                $leftLow = is_array($left) ? $left['low'] : $left->low;
                $rightLow = is_array($right) ? $right['low'] : $right->low;

                if ($leftHigh >= $currentHigh || $rightHigh >= $currentHigh) {
                    $isHigh = false;
                }
                if ($leftLow <= $currentLow || $rightLow <= $currentLow) {
                    $isLow = false;
                }
            }

            if ($isHigh) {
                $openTime = is_array($current) ? $current['open_time'] : $current->open_time;
                $swings->push([
                    'type' => 'high', 
                    'price' => $currentHigh, 
                    'time' => $openTime,
                    'index' => $i
                ]);
            }
            if ($isLow) {
                $openTime = is_array($current) ? $current['open_time'] : $current->open_time;
                $swings->push([
                    'type' => 'low', 
                    'price' => $currentLow, 
                    'time' => $openTime,
                    'index' => $i
                ]);
            }
        }

        return $swings;
    }

    /**
     * Identifikasi Break of Structure (BOS) dan Change of Character (CHoCH)
     * dan simpan ke tabel market_structures.
     */
    protected function identifyStructures(Pair $pair, string $timeframe, Collection $swings): void
    {
        // Ambil swing highs dan lows secara terpisah, diurutkan berdasarkan waktu
        $swingHighs = $swings->where('type', 'high')->sortBy('index')->values();
        $swingLows = $swings->where('type', 'low')->sortBy('index')->values();
        
        if ($swingHighs->count() < 2 || $swingLows->count() < 2) {
            return;
        }

        // Tentukan tren terakhir berdasarkan perbandingan swing points
        // Higher High + Higher Low = Bullish
        // Lower High + Lower Low = Bearish
        
        $lastSH1 = $swingHighs[$swingHighs->count() - 2];
        $lastSH2 = $swingHighs[$swingHighs->count() - 1]; // paling baru
        $lastSL1 = $swingLows[$swingLows->count() - 2];
        $lastSL2 = $swingLows[$swingLows->count() - 1]; // paling baru
        
        $higherHigh = $lastSH2['price'] > $lastSH1['price'];
        $higherLow = $lastSL2['price'] > $lastSL1['price'];
        $lowerHigh = $lastSH2['price'] < $lastSH1['price'];
        $lowerLow = $lastSL2['price'] < $lastSL1['price'];
        
        // Tentukan tipe struktur
        $structureType = null;
        $direction = null;
        $price = null;
        $detectedAt = $lastSH2['time'];
        
        if ($higherHigh && $higherLow) {
            // Bullish structure - BOS bullish (harga break high sebelumnya)
            $structureType = 'BOS';
            $direction = 'bullish';
            $price = $lastSH2['price'];
        } elseif ($lowerHigh && $lowerLow) {
            // Bearish structure - BOS bearish (harga break low sebelumnya)
            $structureType = 'BOS';
            $direction = 'bearish';
            $price = $lastSL2['price'];
            $detectedAt = $lastSL2['time'];
        } elseif ($higherHigh && $lowerLow) {
            // Volatile / ranging
            $structureType = 'BOS';
            $direction = 'bullish';
            $price = $lastSH2['price'];
        } elseif ($lowerHigh && $higherLow) {
            // Consolidation
            return; // Tidak simpan, tetap ranging
        } elseif ($lowerLow && !$lowerHigh) {
            // CHoCH bearish - sebelumnya bullish tapi sekarang buat lower low
            $structureType = 'CHoCH';
            $direction = 'bearish';
            $price = $lastSL2['price'];
            $detectedAt = $lastSL2['time'];
        } elseif ($higherHigh && !$higherLow) {
            // CHoCH bullish - sebelumnya bearish tapi sekarang buat higher high
            $structureType = 'CHoCH';
            $direction = 'bullish';
            $price = $lastSH2['price'];
        }
        
        if ($structureType && $direction) {
            MarketStructure::updateOrCreate(
                [
                    'pair_id' => $pair->id,
                    'timeframe' => $timeframe,
                ],
                [
                    'type' => $structureType,
                    'direction' => $direction,
                    'price' => $price,
                    'detected_at' => $detectedAt,
                ]
            );
            
            Log::info("Struktur {$structureType} {$direction} terdeteksi pada {$pair->symbol} {$timeframe} di harga {$price}");
        }
    }

    public function determineTrend(Pair $pair, string $timeframe): string
    {
        // Berdasarkan BOS/CHoCH terakhir, tentukan apakah Bullish/Bearish
        $lastStructure = MarketStructure::where('pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->orderBy('detected_at', 'desc')
            ->first();
            
        if (!$lastStructure) return 'ranging';
        
        if (in_array($lastStructure->type, ['BOS', 'CHoCH']) && $lastStructure->direction === 'bullish') return 'bullish';
        if (in_array($lastStructure->type, ['BOS', 'CHoCH']) && $lastStructure->direction === 'bearish') return 'bearish';
        
        return 'ranging';
    }
}
