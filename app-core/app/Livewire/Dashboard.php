<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Pair;
use App\Models\AnalysisReport;
use App\Models\MarketStructure;
// use App\Services\Smc\TopDownAnalysisOrchestrator;

class Dashboard extends Component
{
    public $pairs = [];
    public $selectedPairId = null;
    
    // Data untuk Chart dan UI
    public $currentReport = null;
    public $biases = [];
    public $keyLevels = [];
    
    public function mount()
    {
        // Load pairs from DB
        $this->pairs = Pair::where('is_active', true)->get()->toArray();
        
        if (count($this->pairs) > 0) {
            $this->selectedPairId = $this->pairs[0]['id'];
            $this->loadPairData();
        }
    }
    
    public function selectPair($id)
    {
        $this->selectedPairId = $id;
        $this->loadPairData();
        
        // Dispatch event ke frontend (TradingView Widget)
        $this->dispatch('pair-changed', symbol: $this->getSelectedSymbol());
    }
    
    public function loadPairData()
    {
        // Query riil dari database
        $report = AnalysisReport::where('pair_id', $this->selectedPairId)->latest()->first();
        
        if ($report) {
            $this->currentReport = [
                'bias' => $report->bias,
                'score' => $report->score,
                'primary_scenario' => [
                    'target_level' => $report->primary_scenario['target_level'] ?? 'N/A',
                    'description' => $report->primary_scenario['description'] ?? 'Tidak ada data skenario.',
                    'probability_percentage' => $report->primary_scenario['probability_percentage'] ?? $report->score,
                ],
                'alt_scenario' => [
                    'invalidation_level' => $report->alt_scenario['invalidation_level'] ?? 'N/A',
                    'description' => $report->alt_scenario['description'] ?? 'Tidak ada skenario alternatif.'
                ],
                'updated_at' => $report->created_at->format('Y-m-d H:i:s')
            ];
            
            // Ambil bias per-timeframe dari tabel market_structures
            $timeframes = ['D1', 'H4', 'H1', 'M30', 'M15', 'M5', 'M1'];
            $this->biases = [];
            foreach ($timeframes as $tf) {
                $ms = MarketStructure::where('pair_id', $this->selectedPairId)
                    ->where('timeframe', $tf)
                    ->orderBy('detected_at', 'desc')
                    ->first();
                $this->biases[$tf] = $ms ? $ms->direction : 'ranging';
            }
            
            // Ambil key levels dari market_structures sebagai POI
            $this->keyLevels = [];
            $structures = MarketStructure::where('pair_id', $this->selectedPairId)
                ->orderBy('detected_at', 'desc')
                ->limit(5)
                ->get();
            foreach ($structures as $s) {
                $decimals = 5;
                $pair = collect($this->pairs)->firstWhere('id', $this->selectedPairId);
                if ($pair && isset($pair['pip_digit'])) {
                    $decimals = $pair['pip_digit'] + 1;
                }
                $this->keyLevels[] = [
                    'type' => $s->type . ' ' . ucfirst($s->direction),
                    'price' => number_format($s->price, $decimals, '.', ''),
                    'distance' => '-',
                    'tf' => $s->timeframe,
                ];
            }
            
            // Tambahkan target & invalidation sebagai POI juga
            if (isset($report->primary_scenario['target_level']) && $report->primary_scenario['target_level'] !== 'N/A') {
                $this->keyLevels[] = [
                    'type' => 'Target (' . ucfirst($report->bias) . ')',
                    'price' => $report->primary_scenario['target_level'],
                    'distance' => 'Target',
                    'tf' => 'H1',
                ];
            }
        }
    }
    
    private function getSelectedSymbol()
    {
        foreach ($this->pairs as $p) {
            if ($p['id'] == $this->selectedPairId) return $p['symbol'];
        }
        return 'EURUSD';
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('components.layouts.app');
    }
}
