<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use Illuminate\Support\Collection;

class BacktestEngine
{
    /**
     * Run backtest for a specific pair and timeframe
     */
    public function run(Pair $pair, string $timeframe)
    {
        // Get all historical candles for this pair/tf, ordered chronologically
        $candles = Candle::where('pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->orderBy('open_time', 'asc')
            ->get();
            
        if ($candles->count() < 100) {
            return $this->emptyResult($pair->symbol, $timeframe);
        }

        $trades = [];
        $totalWins = 0;
        $totalLosses = 0;
        $totalPnl = 0; // Using R:R units (1 R = 1 unit of risk)
        $consecutiveLosses = 0;
        $maxConsecutiveLosses = 0;
        $currentConsecutiveLosses = 0;

        // Settings
        $windowSize = 100; // Lookback window for market structure
        $fractalPeriod = 2; // n-bar fractal
        
        $activeTrade = null; // null or array

        // Sliding window approach
        for ($i = $windowSize; $i < $candles->count(); $i++) {
            $currentCandle = $candles[$i];
            
            // If we have an active trade, check if it hit TP or SL
            if ($activeTrade) {
                $high = $currentCandle->high;
                $low = $currentCandle->low;
                
                $closed = false;
                
                if ($activeTrade['type'] === 'BUY') {
                    if ($low <= $activeTrade['sl']) {
                        // Stop Loss Hit (-1R)
                        $activeTrade['status'] = 'LOSS';
                        $activeTrade['pnl_r'] = -1;
                        $activeTrade['exit_time'] = $currentCandle->open_time;
                        $activeTrade['exit_price'] = $activeTrade['sl'];
                        $closed = true;
                    } elseif ($high >= $activeTrade['tp']) {
                        // Take Profit Hit (+2R)
                        $activeTrade['status'] = 'WIN';
                        $activeTrade['pnl_r'] = 2;
                        $activeTrade['exit_time'] = $currentCandle->open_time;
                        $activeTrade['exit_price'] = $activeTrade['tp'];
                        $closed = true;
                    }
                } else { // SELL
                    if ($high >= $activeTrade['sl']) {
                        // Stop Loss Hit (-1R)
                        $activeTrade['status'] = 'LOSS';
                        $activeTrade['pnl_r'] = -1;
                        $activeTrade['exit_time'] = $currentCandle->open_time;
                        $activeTrade['exit_price'] = $activeTrade['sl'];
                        $closed = true;
                    } elseif ($low <= $activeTrade['tp']) {
                        // Take Profit Hit (+2R)
                        $activeTrade['status'] = 'WIN';
                        $activeTrade['pnl_r'] = 2;
                        $activeTrade['exit_time'] = $currentCandle->open_time;
                        $activeTrade['exit_price'] = $activeTrade['tp'];
                        $closed = true;
                    }
                }
                
                if ($closed) {
                    $trades[] = $activeTrade;
                    $totalPnl += $activeTrade['pnl_r'];
                    
                    if ($activeTrade['status'] === 'WIN') {
                        $totalWins++;
                        $currentConsecutiveLosses = 0;
                    } else {
                        $totalLosses++;
                        $currentConsecutiveLosses++;
                        if ($currentConsecutiveLosses > $maxConsecutiveLosses) {
                            $maxConsecutiveLosses = $currentConsecutiveLosses;
                        }
                    }
                    $activeTrade = null;
                }
                
                continue; // Wait for trade to finish before looking for new setups
            }
            
            // If no active trade, look for setup
            // Get window of past candles
            $window = $candles->slice($i - $windowSize, $windowSize)->values();
            
            // Detect Trend simply by checking highest high and lowest low of last 30 candles
            // Real SMC is complex to simulate fast, so we use a proxy for Scalping momentum
            $recent = $window->slice(-30)->values();
            $recentHigh = $recent->max('high');
            $recentLow = $recent->min('low');
            
            // Is current price near OTE?
            $close = $currentCandle->close;
            $range = $recentHigh - $recentLow;
            if ($range == 0) continue;
            
            // Simple momentum proxy: if close is above SMA50, bias is Bullish
            $sma50 = $window->slice(-50)->avg('close');
            $bias = ($close > $sma50) ? 'bullish' : 'bearish';
            
            $decimals = ($pair->pip_digit ?? 4) + 1;
            $buffer = 2 / pow(10, $pair->pip_digit ?? 4);
            
            if ($bias === 'bullish') {
                $ote = $recentLow + ($range * 0.33); // Discount
                if ($close <= $ote && $close > $recentLow) {
                    // Trigger BUY Limit
                    $sl = $recentLow - $buffer;
                    $risk = $close - $sl;
                    if ($risk <= 0) $risk = 0.0001;
                    $tp = $close + ($risk * 2); // 1:2 RR
                    
                    $activeTrade = [
                        'type' => 'BUY',
                        'entry_time' => $currentCandle->open_time,
                        'entry_price' => round($close, $decimals),
                        'sl' => round($sl, $decimals),
                        'tp' => round($tp, $decimals),
                    ];
                }
            } else {
                $ote = $recentHigh - ($range * 0.33); // Premium
                if ($close >= $ote && $close < $recentHigh) {
                    // Trigger SELL Limit
                    $sl = $recentHigh + $buffer;
                    $risk = $sl - $close;
                    if ($risk <= 0) $risk = 0.0001;
                    $tp = $close - ($risk * 2); // 1:2 RR
                    
                    $activeTrade = [
                        'type' => 'SELL',
                        'entry_time' => $currentCandle->open_time,
                        'entry_price' => round($close, $decimals),
                        'sl' => round($sl, $decimals),
                        'tp' => round($tp, $decimals),
                    ];
                }
            }
        }
        
        $totalTrades = count($trades);
        $winRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;
        
        return [
            'symbol' => $pair->symbol,
            'timeframe' => $timeframe,
            'total_trades' => $totalTrades,
            'wins' => $totalWins,
            'losses' => $totalLosses,
            'win_rate' => $winRate,
            'total_pnl_r' => $totalPnl,
            'max_drawdown' => $maxConsecutiveLosses,
            'trades' => array_reverse($trades), // newest first
        ];
    }
    
    private function emptyResult($symbol, $tf)
    {
         return [
            'symbol' => $symbol,
            'timeframe' => $tf,
            'total_trades' => 0,
            'wins' => 0,
            'losses' => 0,
            'win_rate' => 0,
            'total_pnl_r' => 0,
            'max_drawdown' => 0,
            'trades' => [],
        ];
    }
}
