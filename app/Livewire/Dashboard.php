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
        // Mock data untuk keperluan MVP UI
        $this->pairs = [
            ['id' => 1, 'symbol' => 'EURUSD'],
            ['id' => 2, 'symbol' => 'GBPUSD'],
            ['id' => 3, 'symbol' => 'XAUUSD'],
        ];
        
        $this->selectedPairId = 1;
        $this->loadPairData();
    }
    
    public function selectPair($id)
    {
        $this->selectedPairId = $id;
        $this->loadPairData();
        
        // Dispatch event ke frontend (AlpineJS/Lightweight Charts)
        $this->dispatch('pair-changed', symbol: $this->getSelectedSymbol());
    }
    
    public function loadPairData()
    {
        // Di aplikasi asli: query dari database
        // $this->currentReport = AnalysisReport::where('pair_id', $this->selectedPairId)->latest()->first();
        
        // Mock Data
        $this->currentReport = [
            'bias' => 'bullish',
            'score' => 85,
            'primary_scenario' => [
                'target_level' => '1.11500',
                'description' => 'Harga berada di OTE discount, ekspektasi ekspansi ke Buy-side Liquidity terdekat.',
                'probability_percentage' => 85
            ],
            'alt_scenario' => [
                'invalidation_level' => '1.09850',
                'description' => 'Jika menembus swing low terakhir, struktur H1 berubah menjadi bearish.'
            ],
            'updated_at' => now()->format('Y-m-d H:i:s')
        ];
        
        $this->biases = [
            'D1' => 'bullish',
            'H4' => 'bullish',
            'H1' => 'bullish',
            'M30' => 'ranging',
            'M15' => 'bullish',
            'M5' => 'bullish',
            'M1' => 'bearish',
        ];
        
        $this->keyLevels = [
            ['type' => 'Bullish OB', 'price' => '1.10250', 'distance' => '15 pips', 'tf' => 'M15'],
            ['type' => 'Unfilled FVG', 'price' => '1.10300', 'distance' => '10 pips', 'tf' => 'H1'],
            ['type' => 'EQH (Buy-side)', 'price' => '1.11500', 'distance' => '110 pips', 'tf' => 'H4'],
        ];
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
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
