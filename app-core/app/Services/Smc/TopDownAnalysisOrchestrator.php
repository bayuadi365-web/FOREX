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
            // Ambil candle dari DB
            $candles = Candle::where('pair_id', $pair->id)
                ->where('timeframe', $tf)
                ->orderBy('open_time', 'asc')
                ->limit(200)
                ->get();
            
            if ($candles->count() >= 10) {
                // Jalankan analisa penuh (deteksi fractal + BOS/CHoCH + simpan ke DB)
                $biases[$tf] = $this->msEngine->analyze($pair, $tf, $candles);
            } else {
                $biases[$tf] = 'ranging';
            }
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
            'choch_bos_confirmed' => $this->hasBosOrChoch($biases),
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

        // Bangun Skenario Utama & Alternatif dari data riil
        $primaryScenario = $this->buildPrimaryScenario($pair, $mainBias, $score);
        $altScenario = $this->buildAltScenario($pair, $mainBias);

        // Generate Report
        $report = $this->scoringEngine->generateReport($pair, $score, $mainBias, $primaryScenario, $altScenario);
        
        Log::info("Analysis Report terbentuk untuk {$pair->symbol}. Skor: {$score}, Bias: {$mainBias}");
        
        return $report;
    }

    /**
     * Cek apakah ada minimal 1 BOS atau CHoCH yang terdeteksi
     */
    protected function hasBosOrChoch(array $biases): bool
    {
        foreach ($biases as $b) {
            if ($b !== 'ranging') return true;
        }
        return false;
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
        $lastCandle = Candle::where('pair_id', $pair->id)->orderBy('open_time', 'desc')->first();
        
        $recentCandles = Candle::where('pair_id', $pair->id)
            ->where('timeframe', 'H1')
            ->orderBy('open_time', 'desc')
            ->limit(50)
            ->get();
        
        $currentPrice = $lastCandle ? $lastCandle->close : 0;
        $recentHigh = $recentCandles->max('high') ?? $currentPrice;
        $recentLow = $recentCandles->min('low') ?? $currentPrice;
        $range = $recentHigh - $recentLow;
        
        $decimals = ($pair->pip_digit ?? 4) + 1;
        
        if ($bias === 'bullish') {
            $targetLevel = number_format($recentHigh, $decimals, '.', '');
            // Entry ideal untuk RR 1:2 adalah di 1/3 terbawah dari range (Discount Zone)
            $otePrice = $recentLow + ($range / 3);
            $otePriceFormatted = number_format($otePrice, $decimals, '.', '');
            
            $description = "Target Buy-side Liquidity di {$targetLevel}. ";
            if ($currentPrice > $otePrice) {
                $description .= "R:R saat ini < 1:2. Tunggu retracement turun ke area Discount (sekitar {$otePriceFormatted}) sebelum masuk BUY.";
            } else {
                $description .= "Harga saat ini di area Discount (R:R > 1:2). Peluang BUY yang ideal.";
            }
            
        } elseif ($bias === 'bearish') {
            $targetLevel = number_format($recentLow, $decimals, '.', '');
            // Entry ideal untuk RR 1:2 adalah di 1/3 teratas dari range (Premium Zone)
            $otePrice = $recentHigh - ($range / 3);
            $otePriceFormatted = number_format($otePrice, $decimals, '.', '');
            
            $description = "Target Sell-side Liquidity di {$targetLevel}. ";
            if ($currentPrice < $otePrice) {
                $description .= "R:R saat ini < 1:2. Tunggu retracement naik ke area Premium (sekitar {$otePriceFormatted}) sebelum masuk SELL.";
            } else {
                $description .= "Harga saat ini di area Premium (R:R > 1:2). Peluang SELL yang ideal.";
            }
            
        } else {
            $targetLevel = number_format($currentPrice, $decimals, '.', '');
            $description = "Struktur belum jelas. Tunggu konfirmasi BOS/CHoCH.";
        }
        
        return [
            'description' => $description,
            'target_level' => $targetLevel,
            'path' => 'Menunggu Retracement ke OTE (Optimal Trade Entry) lalu ekspansi.',
            'probability_percentage' => $score
        ];
    }

    protected function buildAltScenario(Pair $pair, string $bias): array
    {
        // Ambil swing low/high terdekat sebagai invalidation level
        $recentCandles = Candle::where('pair_id', $pair->id)
            ->where('timeframe', 'H1')
            ->orderBy('open_time', 'desc')
            ->limit(50)
            ->get();
        
        $recentHigh = $recentCandles->max('high') ?? 0;
        $recentLow = $recentCandles->min('low') ?? 0;
        
        $decimals = ($pair->pip_digit ?? 4) + 1;
        
        if ($bias === 'bullish') {
            $invalidation = number_format($recentLow, $decimals, '.', '');
            $description = "Jika harga menembus di bawah {$invalidation} (swing low terakhir), bias berubah menjadi bearish.";
        } elseif ($bias === 'bearish') {
            $invalidation = number_format($recentHigh, $decimals, '.', '');
            $description = "Jika harga menembus di atas {$invalidation} (swing high terakhir), bias berubah menjadi bullish.";
        } else {
            $lastCandle = Candle::where('pair_id', $pair->id)->orderBy('open_time', 'desc')->first();
            $invalidation = $lastCandle ? number_format($lastCandle->close, $decimals, '.', '') : 'N/A';
            $description = "Menunggu konfirmasi arah pasar. Pantau level {$invalidation}.";
        }
        
        return [
            'invalidation_level' => $invalidation,
            'description' => $description
        ];
    }
}
