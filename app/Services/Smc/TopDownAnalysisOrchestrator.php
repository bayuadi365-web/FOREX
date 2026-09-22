<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\Candle;
use Illuminate\Support\Facades\Log;

class TopDownAnalysisOrchestrator
{
    protected MarketStructureEngine $msEngine;
    protected ConfluenceScoringEngine $scoringEngine;

    // Timeframe hirarki dari atas ke bawah
    const TIMEFRAMES = ['D1', 'H4', 'H1', 'M30', 'M15', 'M5', 'M1'];

    public function __construct(
        MarketStructureEngine $msEngine,
        ConfluenceScoringEngine $scoringEngine
    ) {
        $this->msEngine = $msEngine;
        $this->scoringEngine = $scoringEngine;
    }

    /**
     * Menjalankan analisa Top-Down untuk suatu Pair
     */
    public function runAnalysis(Pair $pair)
    {
        Log::info("Memulai Top-Down Analysis untuk {$pair->symbol}");
        
        $biases = [];
        $hasStructureConflict = false;
        
        foreach (self::TIMEFRAMES as $tf) {
            // Analisa trend di timeframe ini
            $biases[$tf] = $this->msEngine->determineTrend($pair, $tf);
        }

        // Cek konflik struktur (contoh: M30 vs M15)
        if ($biases['M30'] !== 'ranging' && $biases['M15'] !== 'ranging') {
            if ($biases['M30'] !== $biases['M15']) {
                $hasStructureConflict = true;
                Log::warning("Terdeteksi konflik struktur pada {$pair->symbol} (M30: {$biases['M30']}, M15: {$biases['M15']})");
            }
        }

        // Tentukan bias utama dari HTF (H1, H4, D1)
        $mainBias = $this->determineMainBias([$biases['D1'], $biases['H4'], $biases['H1']]);
        
        // Cek jika sinyal LTF (M5/M1) melawan HTF
        $isLtfAligned = true;
        if ($mainBias !== 'neutral') {
            if (in_array($biases['M1'], ['bullish', 'bearish']) && $biases['M1'] !== $mainBias) $isLtfAligned = false;
            if (in_array($biases['M5'], ['bullish', 'bearish']) && $biases['M5'] !== $mainBias) $isLtfAligned = false;
        }

        // Kalkulasi Skor Confluence
        $factors = [
            'htf_bias_aligned' => $isLtfAligned && !$hasStructureConflict,
            // ... (Faktor lain akan diisi oleh engine masing-masing saat integrasi penuh)
            'choch_bos_confirmed' => true, 
            'fresh_ob' => true,
        ];
        
        $score = $this->scoringEngine->calculateScore($factors);
        
        // Turunkan skor secara drastis jika ada konflik struktur
        if ($hasStructureConflict) {
            $score = (int)($score * 0.3); // Diskon 70%
        }
        
        // Sinyal LTF melawan HTF tidak boleh High Probability
        if (!$isLtfAligned && $score >= 75) {
            $score = 74; 
        }

        // Bangun Skenario Utama & Alternatif
        $primaryScenario = $this->buildPrimaryScenario($pair, $mainBias, $score);
        $altScenario = $this->buildAltScenario($pair, $mainBias);

        // Generate Report
        $report = $this->scoringEngine->generateReport($pair, $score, $mainBias, $primaryScenario, $altScenario);
        
        Log::info("Analysis Report terbentuk untuk {$pair->symbol}. Skor: {$score}, Bias: {$mainBias}");
        
        return $report;
    }

    protected function determineMainBias(array $htfBiases): string
    {
        $bullishCount = count(array_filter($htfBiases, fn($b) => $b === 'bullish'));
        $bearishCount = count(array_filter($htfBiases, fn($b) => $b === 'bearish'));

        if ($bullishCount >= 2) return 'bullish';
        if ($bearishCount >= 2) return 'bearish';
        return 'neutral';
    }

    protected function buildPrimaryScenario(Pair $pair, string $bias, int $score): array
    {
        // Dalam implementasi penuh, data ini diambil dari detektor Liquidity & OB
        return [
            'description' => "Harga diperkirakan melanjutkan struktur {$bias} menuju level likuiditas terdekat.",
            'target_level' => '1.1050', // Mock
            'path' => 'Retracement ke Order Block M15 lalu ekspansi.',
            'probability_percentage' => $score
        ];
    }

    protected function buildAltScenario(Pair $pair, string $bias): array
    {
        $invBias = $bias === 'bullish' ? 'bearish' : 'bullish';
        return [
            'invalidation_level' => '1.0950', // Mock (di bawah swing low)
            'description' => "Jika harga menembus Invalidation Level, bias berubah menjadi {$invBias}."
        ];
    }
}
