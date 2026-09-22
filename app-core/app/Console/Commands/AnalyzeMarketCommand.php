<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Services\Smc\TopDownAnalysisOrchestrator;

class AnalyzeMarketCommand extends Command
{
    protected $signature = 'smc:analyze {--pair= : Spesifik pair (opsional)}';
    protected $description = 'Menjalankan SMC Top Down Analysis untuk pair aktif';

    public function handle(TopDownAnalysisOrchestrator $orchestrator)
    {
        $pairSymbol = $this->option('pair');
        $query = Pair::where('is_active', true);
        
        if ($pairSymbol) {
            $query->where('symbol', strtoupper($pairSymbol));
        }
        
        $pairs = $query->get();
        
        if ($pairs->isEmpty()) {
            $this->warn("Tidak ada pair aktif yang ditemukan.");
            return;
        }

        $this->info("Memulai proses analisis SMC...");

        foreach ($pairs as $pair) {
            $this->info("Menganalisis {$pair->symbol}...");
            try {
                $report = $orchestrator->runAnalysis($pair);
                $this->info("  -> Sukses! Bias: {$report->bias}, Score: {$report->score}");
            } catch (\Exception $e) {
                $this->error("  -> Gagal: " . $e->getMessage());
            }
        }
        
        $this->info("Analisis selesai.");
    }
}
