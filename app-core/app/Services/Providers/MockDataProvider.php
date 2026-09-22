<?php

namespace App\Services\Providers;

use App\Contracts\MarketDataProvider;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class MockDataProvider implements MarketDataProvider
{
    /**
     * @inheritDoc
     */
    public function getHistoricalData(string $symbol, string $timeframe, int $limit = 500): Collection
    {
        $data = collect();
        
        // Asumsikan data berakhir 1 jam lalu untuk simulasi
        $endTime = Carbon::now()->subHour()->startOfMinute();
        
        // Tentukan interval menit berdasarkan timeframe
        $minutes = $this->getMinutesFromTimeframe($timeframe);
        
        $currentPrice = 1.1000; // Harga awal (misal untuk EURUSD)
        
        // Generate mock candles dari lama ke baru
        for ($i = $limit - 1; $i >= 0; $i--) {
            $time = (clone $endTime)->subMinutes($i * $minutes);
            
            // Random walk dengan sedikit bias untuk menciptakan tren
            $volatility = 0.0010;
            $open = $currentPrice;
            $close = $open + (rand(-100, 100) / 100) * $volatility;
            $high = max($open, $close) + (rand(0, 50) / 100) * $volatility;
            $low = min($open, $close) - (rand(0, 50) / 100) * $volatility;
            
            $data->push([
                'open_time' => $time->format('Y-m-d H:i:s'),
                'open' => round($open, 5),
                'high' => round($high, 5),
                'low' => round($low, 5),
                'close' => round($close, 5),
                'volume' => rand(100, 10000),
            ]);
            
            $currentPrice = $close;
        }
        
        return $data;
    }

    public function subscribe(string $symbol, callable $onMessage): void
    {
        // Not implemented for mock
    }
    
    private function getMinutesFromTimeframe(string $timeframe): int
    {
        return match ($timeframe) {
            'M1' => 1,
            'M5' => 5,
            'M15' => 15,
            'M30' => 30,
            'H1' => 60,
            'H4' => 240,
            'D1' => 1440,
            default => 60,
        };
    }
}
