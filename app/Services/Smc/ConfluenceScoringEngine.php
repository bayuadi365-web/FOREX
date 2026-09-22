<?php

namespace App\Services\Smc;

use App\Models\Pair;
use App\Models\IndicatorWeight;
use App\Models\AnalysisReport;
use Carbon\Carbon;

class ConfluenceScoringEngine
{
    protected $weights = [];

    public function __construct()
    {
        // Load weights from database
        // Jika database error, gunakan default array
        try {
            $this->weights = IndicatorWeight::pluck('weight_value', 'indicator_key')->toArray();
        } catch (\Exception $e) {
            $this->weights = [
                'htf_bias_aligned' => 25,
                'choch_bos_confirmed' => 20,
                'fresh_ob' => 15,
                'unfilled_fvg' => 10,
                'liquidity_sweep' => 15,
                'premium_discount' => 10,
                'in_killzone' => 5,
            ];
        }
    }

    /**
     * Kalkulasi total skor probabilitas
     */
    public function calculateScore(array $factors): int
    {
        $score = 0;
        foreach ($factors as $key => $isMet) {
            if ($isMet && isset($this->weights[$key])) {
                $score += $this->weights[$key];
            }
        }
        
        return min(100, $score); // Cap di 100
    }

    /**
     * Konversi angka ke kategori
     */
    public function getLabel(int $score): string
    {
        if ($score >= 75) return 'High Probability';
        if ($score >= 50) return 'Medium Probability';
        return 'Low Probability';
    }

    /**
     * Generate Analysis Report setelah semua engine berjalan
     */
    public function generateReport(Pair $pair, int $score, string $bias, array $primaryScenario, array $altScenario)
    {
        return AnalysisReport::create([
            'pair_id' => $pair->id,
            'bias' => $bias,
            'score' => $score,
            'primary_scenario' => $primaryScenario,
            'alt_scenario' => $altScenario,
            'generated_at' => Carbon::now(),
        ]);
    }
}
