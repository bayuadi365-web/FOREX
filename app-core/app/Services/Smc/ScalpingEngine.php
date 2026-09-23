<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use App\Models\MarketStructure;
use Illuminate\Support\Facades\Log;

class ScalpingEngine
{
    /**
     * Scan scalping opportunities for specific pair and timeframe
     */
    public function scan(Pair $pair, string $timeframe)
    {
        // For scalping, we look at M1, M5, M15
        if (!in_array($timeframe, ['M1', 'M5', 'M15'])) {
            return null;
        }

        // Get recent market structure
        $latestMs = MarketStructure::where('pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->orderBy('detected_at', 'desc')
            ->first();

        if (!$latestMs) {
            return $this->buildResult($pair, $timeframe, 'pending', 'neutral', 'Waiting for structure', 0, 0, 0, 0);
        }

        $bias = $latestMs->direction; // bullish or bearish
        
        // Get latest candle
        $lastCandle = Candle::where('pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->orderBy('open_time', 'desc')
            ->first();

        // Get range for calculation
        $recentCandles = Candle::where('pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->where('open_time', '>=', $latestMs->detected_at)
            ->orderBy('open_time', 'desc')
            ->limit(30)
            ->get();
            
        // If not enough data since last MS, take last 30
        if ($recentCandles->count() < 10) {
            $recentCandles = Candle::where('pair_id', $pair->id)
                ->where('timeframe', $timeframe)
                ->orderBy('open_time', 'desc')
                ->limit(30)
                ->get();
        }

        $currentPrice = $lastCandle ? $lastCandle->close : 0;
        $recentHigh = $recentCandles->max('high') ?? $currentPrice;
        $recentLow = $recentCandles->min('low') ?? $currentPrice;
        $range = $recentHigh - $recentLow;
        
        // Prevent division by zero
        if ($range == 0) $range = 0.0001;
        
        $decimals = ($pair->pip_digit ?? 4) + 1;
        
        $entry = 0;
        $sl = 0;
        $tp1 = 0;
        $tp2 = 0;
        $status = 'pending';
        $message = '';
        
        // Scalping OTE (62% - 79% retracement is standard SMC, but we'll use 1/3 calculation for simplicity & math lock)
        if ($bias === 'bullish') {
            // Entry at Discount (lower third)
            $entry = $recentLow + ($range * 0.33); 
            // SL strictly below recent swing low + small buffer
            $buffer = 2 / pow(10, $pair->pip_digit ?? 4); // 2 pips buffer
            $sl = $recentLow - $buffer;
            
            $risk = $entry - $sl;
            if ($risk <= 0) $risk = 0.0001;
            
            // Lock RR 1:2 and 1:3
            $tp1 = $entry + ($risk * 2);
            $tp2 = $entry + ($risk * 3);
            
            if ($currentPrice <= $entry && $currentPrice > $sl) {
                $status = 'active';
                $message = "In Entry Zone (Discount)";
            } elseif ($currentPrice > $entry) {
                $status = 'pending';
                $message = "Waiting for pullback";
            } else {
                $status = 'invalid';
                $message = "SL Hit / Trend shifting";
            }
            
        } else { // bearish
            // Entry at Premium (upper third)
            $entry = $recentHigh - ($range * 0.33);
            // SL strictly above recent swing high + small buffer
            $buffer = 2 / pow(10, $pair->pip_digit ?? 4);
            $sl = $recentHigh + $buffer;
            
            $risk = $sl - $entry;
            if ($risk <= 0) $risk = 0.0001;
            
            // Lock RR 1:2 and 1:3
            $tp1 = $entry - ($risk * 2);
            $tp2 = $entry - ($risk * 3);
            
            if ($currentPrice >= $entry && $currentPrice < $sl) {
                $status = 'active';
                $message = "In Entry Zone (Premium)";
            } elseif ($currentPrice < $entry) {
                $status = 'pending';
                $message = "Waiting for pullback";
            } else {
                $status = 'invalid';
                $message = "SL Hit / Trend shifting";
            }
        }
        
        return $this->buildResult(
            $pair, 
            $timeframe, 
            $status, 
            $bias, 
            $message, 
            number_format($entry, $decimals, '.', ''),
            number_format($sl, $decimals, '.', ''),
            number_format($tp1, $decimals, '.', ''),
            number_format($tp2, $decimals, '.', ''),
            $currentPrice
        );
    }
    
    private function buildResult($pair, $tf, $status, $bias, $msg, $entry, $sl, $tp1, $tp2, $currentPrice = 0)
    {
        return [
            'symbol' => $pair->symbol,
            'timeframe' => $tf,
            'status' => $status, // active, pending, invalid
            'bias' => $bias, // bullish, bearish
            'message' => $msg,
            'current_price' => $currentPrice,
            'entry' => $entry,
            'sl' => $sl,
            'tp1' => $tp1, // RR 1:2
            'tp2' => $tp2, // RR 1:3
        ];
    }
}
