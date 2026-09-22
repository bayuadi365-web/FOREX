<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Jobs\SyncPairData;

class SyncMarketDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smc:sync-data {--pair= : Spesifik pair (opsional)} {--timeframe= : Spesifik timeframe (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melakukan sinkronisasi data OHLCV untuk pair aktif';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pairSymbol = $this->option('pair');
        $specificTimeframe = $this->option('timeframe');
        
        $query = Pair::where('is_active', true);
        
        if ($pairSymbol) {
            $query->where('symbol', strtoupper($pairSymbol));
        }
        
        $pairs = $query->get();
        
        if ($pairs->isEmpty()) {
            $this->warn("Tidak ada pair aktif yang ditemukan.");
            return;
        }

        $timeframes = ['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1'];
        if ($specificTimeframe && in_array(strtoupper($specificTimeframe), $timeframes)) {
            $timeframes = [strtoupper($specificTimeframe)];
        }
        
        $this->info("Memulai antrean sinkronisasi data...");
        $count = 0;

        foreach ($pairs as $pair) {
            foreach ($timeframes as $tf) {
                // Dispatch job ke queue untuk diproses asinkron oleh worker/Horizon
                SyncPairData::dispatch($pair, $tf)->onQueue('data-ingestion');
                $count++;
            }
        }
        
        $this->info("Berhasil menambahkan {$count} tugas sinkronisasi ke dalam queue 'data-ingestion'.");
    }
}
