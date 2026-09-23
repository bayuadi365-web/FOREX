<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Pair;
use App\Models\Candle;
use App\Services\Smc\BacktestEngine;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Backtester extends Component
{
    use WithFileUploads;

    public $selectedPairId;
    public $selectedTimeframe = 'M15';
    public $pairs;
    public $results = null;
    public $isLoading = false;
    
    // CSV Upload properties
    public $csvFile;
    public $importStatus = '';
    public $importCount = 0;

    public function mount()
    {
        $this->pairs = Pair::where('is_active', true)->get();
        if ($this->pairs->count() > 0) {
            $this->selectedPairId = $this->pairs->first()->id;
        }
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:51200', // max 50MB
        ]);

        $this->importStatus = 'Membaca file...';
        
        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');
        
        // Asumsi format umum MT4/MT5: Date, Time, Open, High, Low, Close, Volume
        // Atau: Date (Y-m-d H:i:s), Open, High, Low, Close
        $header = fgetcsv($file); // Skip header jika ada, tapi kita cek baris pertama
        
        // Rewind jika bukan string (misal data langsung)
        if (is_numeric($header[1]) && is_numeric($header[2])) {
            rewind($file);
        }
        
        $batch = [];
        $count = 0;
        
        while (($row = fgetcsv($file)) !== false) {
            // Abaikan baris kosong
            if (empty($row[0])) continue;
            
            // Format 1: Date, Time, O, H, L, C, V (MT4 standard) -> 2023.01.01, 00:00, 1.1, 1.2, 1.0, 1.1, 100
            // Format 2: DateTime, O, H, L, C -> 2023-01-01 00:00:00, 1.1, 1.2, 1.0, 1.1
            
            try {
                if (count($row) >= 6 && strpos($row[1], ':') !== false && strlen($row[1]) <= 8) {
                    // MT4 Format
                    $dateStr = str_replace('.', '-', $row[0]) . ' ' . $row[1];
                    $open = (float) $row[2];
                    $high = (float) $row[3];
                    $low = (float) $row[4];
                    $close = (float) $row[5];
                    $volume = isset($row[6]) ? (int) $row[6] : 0;
                } else {
                    // Standard Format
                    $dateStr = $row[0];
                    $open = (float) $row[1];
                    $high = (float) $row[2];
                    $low = (float) $row[3];
                    $close = (float) $row[4];
                    $volume = isset($row[5]) ? (int) $row[5] : 0;
                }
                
                $parsedDate = Carbon::parse($dateStr)->format('Y-m-d H:i:s');
                
                $batch[] = [
                    'pair_id' => $this->selectedPairId,
                    'timeframe' => $this->selectedTimeframe,
                    'open_time' => $parsedDate,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'volume' => $volume,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                $count++;
                
                // Insert per 1000 baris agar tidak memakan RAM
                if (count($batch) >= 1000) {
                    Candle::insertOrIgnore($batch);
                    $batch = [];
                }
                
            } catch (\Exception $e) {
                // Skip baris bermasalah
                continue;
            }
        }
        
        // Insert sisa batch
        if (count($batch) > 0) {
            Candle::insertOrIgnore($batch);
        }
        
        fclose($file);
        
        $this->importCount = $count;
        $this->importStatus = "Selesai! $count baris data berhasil diimpor.";
        $this->csvFile = null;
    }

    public function runBacktest(BacktestEngine $engine)

    {
        $this->isLoading = true;
        
        $pair = Pair::find($this->selectedPairId);
        if ($pair) {
            $this->results = $engine->run($pair, $this->selectedTimeframe);
        }
        
        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.backtester')->layout('components.layouts.app');
    }
}
