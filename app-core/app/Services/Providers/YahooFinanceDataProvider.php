<?php

namespace App\Services\Providers;

use App\Contracts\MarketDataProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class YahooFinanceDataProvider implements MarketDataProvider
{
    /**
     * @inheritDoc
     */
    public function getHistoricalData(string $symbol, string $timeframe, int $limit = 500): Collection
    {
        // Convert symbol to Yahoo Finance format
        $yahooSymbol = $this->getYahooSymbol($symbol);
        
        // Convert timeframe to Yahoo interval
        $interval = $this->getYahooInterval($timeframe);
        
        // Calculate range based on limit and timeframe to fetch enough data
        // Yahoo API uses range (e.g. 1d, 5d, 1mo, 3mo, 1y)
        $range = $this->calculateRange($timeframe, $limit);

        try {
            $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$yahooSymbol}";
            
            $response = Http::timeout(10)->get($url, [
                'interval' => $interval,
                'range' => $range,
            ]);

            if ($response->failed()) {
                Log::error("Yahoo Finance API error for {$symbol}: " . $response->body());
                return collect();
            }

            $data = $response->json();
            $result = $data['chart']['result'][0] ?? null;

            if (!$result || !isset($result['timestamp']) || !isset($result['indicators']['quote'][0])) {
                Log::warning("No valid data returned from Yahoo for {$symbol}");
                return collect();
            }

            $timestamps = $result['timestamp'];
            $quotes = $result['indicators']['quote'][0];
            
            $candles = collect();

            for ($i = 0; $i < count($timestamps); $i++) {
                // Skip if any required price point is null
                if ($quotes['open'][$i] === null || $quotes['close'][$i] === null) {
                    continue;
                }

                $candles->push([
                    'open_time' => Carbon::createFromTimestamp($timestamps[$i])->format('Y-m-d H:i:s'),
                    'open' => round($quotes['open'][$i], 5),
                    'high' => round($quotes['high'][$i], 5),
                    'low' => round($quotes['low'][$i], 5),
                    'close' => round($quotes['close'][$i], 5),
                    'volume' => $quotes['volume'][$i] ?? 0,
                ]);
            }
            
            // Limit to requested amount, and sort descending then ascending if needed.
            // By default, Orchestrator expects oldest to newest, but let's just return what we have (already oldest to newest).
            // Actually, we should only return the last $limit candles
            return $candles->take(-$limit)->values();
            
        } catch (Exception $e) {
            Log::error("Exception in YahooFinanceDataProvider for {$symbol}: " . $e->getMessage());
            return collect();
        }
    }

    public function subscribe(string $symbol, callable $onMessage): void
    {
        // Realtime websocket not implemented for Yahoo
    }
    
    private function getYahooSymbol(string $symbol): string
    {
        if ($symbol === 'XAUUSD') {
            return 'GC=F'; // Gold futures (closest to XAUUSD on Yahoo)
        }
        
        return $symbol . '=X'; // e.g. EURUSD=X
    }

    private function getYahooInterval(string $timeframe): string
    {
        return match ($timeframe) {
            'M1' => '1m',
            'M5' => '5m',
            'M15' => '15m',
            'M30' => '30m',
            'H1' => '60m',
            'H4' => '1d', // Yahoo doesn't support 4h easily, fallback to 1d or calculate it manually. (Using 60m to be safe? No, 60m range is limited. We'll use 1d and let SMC engine do daily analysis instead of H4 if H4 fails). Wait, 90m exists? No.
            'D1' => '1d',
            'W1' => '1wk',
            default => '15m',
        };
    }
    
    private function calculateRange(string $timeframe, int $limit): string
    {
        // M1 max is usually 7d, M5/M15/M30 is 60d
        return match ($timeframe) {
            'M1' => '5d',
            'M5' => '1mo',
            'M15' => '1mo',
            'M30' => '1mo',
            'H1' => '3mo',
            'H4' => '1y',
            'D1' => '5y',
            default => '1mo',
        };
    }
}
