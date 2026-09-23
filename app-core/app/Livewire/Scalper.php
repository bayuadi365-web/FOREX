<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Pair;
use App\Services\Smc\ScalpingEngine;

class Scalper extends Component
{
    public $signals = [];
    public $lastUpdated;
    
    public function mount(ScalpingEngine $engine)
    {
        $this->loadSignals($engine);
    }
    
    public function loadSignals(ScalpingEngine $engine)
    {
        // Hanya ambil pair XAU (Gold) untuk menu scalping
        $pairs = Pair::where('is_active', true)
            ->where('symbol', 'like', 'XAU%')
            ->get();
            
        $timeframes = ['M15', 'M5', 'M1'];
        
        $newSignals = [];
        
        foreach ($pairs as $pair) {
            foreach ($timeframes as $tf) {
                $result = $engine->scan($pair, $tf);
                if ($result) {
                    $newSignals[] = $result;
                }
            }
        }
        
        $this->signals = collect($newSignals)->sortBy(function($sig) {
            // Urutkan Active dulu, lalu Pending, lalu Invalid
            $rank = ['active' => 1, 'pending' => 2, 'invalid' => 3];
            $statusRank = $rank[$sig['status']] ?? 4;
            
            // Lalu urutkan TF
            $tfRank = ['M15' => 1, 'M5' => 2, 'M1' => 3];
            $tRank = $tfRank[$sig['timeframe']] ?? 4;
            
            return $statusRank . '-' . $tRank . '-' . $sig['symbol'];
        })->values()->toArray();
        
        $this->lastUpdated = now()->format('H:i:s');
    }

    public function render()
    {
        return view('livewire.scalper')->layout('components.layouts.app');
    }
}
