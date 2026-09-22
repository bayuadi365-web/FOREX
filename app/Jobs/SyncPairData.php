<?php

namespace App\Jobs;

use App\Models\Pair;
use App\Models\Candle;
use App\Contracts\MarketDataProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPairData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pair;
    protected $timeframe;
    
    /**
     * Waktu maksimal job boleh berjalan
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(Pair $pair, string $timeframe)
    {
        $this->pair = $pair;
        $this->timeframe = $timeframe;
    }

    /**
     * Execute the job.
     */
    public function handle(MarketDataProvider $provider): void
    {
        Log::info("Memulai sinkronisasi data {$this->pair->symbol} timeframe {$this->timeframe}");
        
        try {
            // Ambil data dari provider
            $candles = $provider->getHistoricalData($this->pair->symbol, $this->timeframe);
            
            // Siapkan array untuk upsert (idempotent)
            $upsertData = [];
            foreach ($candles as $candle) {
                $upsertData[] = [
                    'pair_id' => $this->pair->id,
                    'timeframe' => $this->timeframe,
                    'open_time' => $candle['open_time'],
                    'open' => $candle['open'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'close' => $candle['close'],
                    'volume' => $candle['volume'],
                ];
            }
            
            // Lakukan upsert berdasarkan unique constraint (pair_id, timeframe, open_time)
            // Ini menjamin idempotency dan kebal terhadap data duplikat
            if (!empty($upsertData)) {
                // Di Laravel, kita gunakan insertOrIgnore atau upsert
                // Dalam kasus ini upsert ideal agar jika ada revisi data dari broker (jarang tapi mungkin), tetap terupdate
                Candle::upsert(
                    $upsertData,
                    ['pair_id', 'timeframe', 'open_time'],
                    ['open', 'high', 'low', 'close', 'volume']
                );
            }
            
            Log::info("Selesai sinkronisasi {$this->pair->symbol} {$this->timeframe}: " . count($upsertData) . " candle");
            
            // TODO: Setelah data tersinkronisasi, trigger recalculation Job untuk SMC Engines
            // misal: dispatch(new AnalyzeMarketStructure($this->pair, $this->timeframe));
            
        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi data {$this->pair->symbol} {$this->timeframe}: " . $e->getMessage());
            throw $e;
        }
    }
}
