<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use App\Models\OrderBlock;
use Illuminate\Support\Collection;

class OrderBlockEngine
{
    public function analyze(Pair $pair, string $timeframe, Collection $candles, $lastBOS = null)
    {
        // 1. Deteksi Fresh Order Blocks (Berdasarkan impulsif move / displacement setelah BOS)
        // Order block bullish: Candle bearish terakhir sebelum pergerakan bullish kuat yang memecah struktur (BOS bullish)
        // Order block bearish: Candle bullish terakhir sebelum pergerakan bearish kuat yang memecah struktur (BOS bearish)
        
        // 2. Cek Mitigasi OB lama
        $this->checkMitigation($pair, $timeframe, $candles->last());
    }

    protected function checkMitigation(Pair $pair, string $timeframe, Candle $latestCandle)
    {
        // Ambil semua OB fresh
        $freshObs = OrderBlock::where('pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->fresh()
            ->get();

        foreach ($freshObs as $ob) {
            // Jika harga menyentuh zona OB
            if ($latestCandle->low <= $ob->top && $latestCandle->high >= $ob->bottom) {
                $ob->status = 'mitigated';
                $ob->mitigated_at = $latestCandle->open_time;
                $ob->save();
            }
        }
    }
}
