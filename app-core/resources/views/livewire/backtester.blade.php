<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Strategy Backtester</h2>
            <p class="text-sm text-slate-400 mt-1">Simulasi historis algoritma OTE Scalping dengan rasio RR 1:2 statis.</p>
        </div>
    </div>

    <!-- Controls -->
    <div class="bg-dark rounded-xl border border-slate-800 shadow-2xl overflow-hidden">
        <!-- Tab 1: Run Simulation -->
        <div class="p-6 border-b border-slate-800">
            <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Jalankan Backtest
            </h3>
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2">Pilih Instrumen</label>
                    <select wire:model="selectedPairId" class="bg-slate-900 border border-slate-700 text-white text-sm rounded-lg focus:ring-brand focus:border-brand block w-full p-2.5 outline-none">
                        @foreach($pairs as $p)
                            <option value="{{ $p->id }}">{{ $p->symbol }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2">Timeframe</label>
                    <select wire:model="selectedTimeframe" class="bg-slate-900 border border-slate-700 text-white text-sm rounded-lg focus:ring-brand focus:border-brand block w-full p-2.5 outline-none">
                        <option value="M1">1 Menit (M1)</option>
                        <option value="M5">5 Menit (M5)</option>
                        <option value="M15">15 Menit (M15)</option>
                        <option value="H1">1 Jam (H1)</option>
                    </select>
                </div>
                
                <div>
                    <button wire:click="runBacktest" wire:loading.attr="disabled" class="bg-brand hover:bg-blue-600 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors shadow-lg shadow-brand/20 disabled:opacity-50">
                        <span wire:loading.remove wire:target="runBacktest">Mulai Simulasi</span>
                        <span wire:loading wire:target="runBacktest">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab 2: Impor Data -->
        <div class="p-6 bg-slate-800/30">
            <h3 class="text-white font-semibold mb-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Impor Data Historis (CSV)
            </h3>
            <p class="text-xs text-slate-400 mb-4 max-w-2xl">Unggah file CSV (misal ekspor dari MT4/MT5) untuk backtest jangka panjang. Pastikan Anda sudah memilih Instrumen dan Timeframe yang sesuai di atas sebelum mengunggah. Format yang didukung: <code>Date, Time, Open, High, Low, Close</code>.</p>
            
            <form wire:submit.prevent="importCsv" class="flex flex-wrap items-center gap-4">
                <input type="file" wire:model="csvFile" class="block w-full md:w-auto text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-600 cursor-pointer" accept=".csv,.txt">
                
                <button type="submit" wire:loading.attr="disabled" class="bg-slate-700 hover:bg-slate-600 text-white border border-slate-600 font-medium rounded-lg text-sm px-5 py-2 text-center transition-colors disabled:opacity-50">
                    <span wire:loading.remove wire:target="importCsv">Upload & Impor</span>
                    <span wire:loading wire:target="importCsv">Mengimpor Data...</span>
                </button>
            </form>
            
            @if($importStatus)
                <div class="mt-3 text-sm {{ str_contains($importStatus, 'Selesai') ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ $importStatus }}
                </div>
            @endif
            @error('csvFile') <span class="mt-2 text-xs text-red-500">{{ $message }}</span> @enderror
        </div>
    </div>

    @if($results)
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-dark rounded-xl border border-slate-800 p-5">
                <div class="text-slate-400 text-xs font-medium mb-1">Win Rate</div>
                <div class="text-3xl font-bold {{ $results['win_rate'] >= 40 ? 'text-green-500' : 'text-red-500' }}">
                    {{ $results['win_rate'] }}%
                </div>
                <div class="text-xs text-slate-500 mt-2">{{ $results['wins'] }} Menang / {{ $results['losses'] }} Kalah</div>
            </div>
            
            <div class="bg-dark rounded-xl border border-slate-800 p-5">
                <div class="text-slate-400 text-xs font-medium mb-1">Total PnL (Risk Units)</div>
                <div class="text-3xl font-bold {{ $results['total_pnl_r'] > 0 ? 'text-green-500' : 'text-red-500' }}">
                    {{ $results['total_pnl_r'] > 0 ? '+' : '' }}{{ $results['total_pnl_r'] }} R
                </div>
                <div class="text-xs text-slate-500 mt-2">1R = Nilai Risiko SL Anda</div>
            </div>
            
            <div class="bg-dark rounded-xl border border-slate-800 p-5">
                <div class="text-slate-400 text-xs font-medium mb-1">Total Trade</div>
                <div class="text-3xl font-bold text-white">
                    {{ $results['total_trades'] }}
                </div>
                <div class="text-xs text-slate-500 mt-2">Peluang ditemukan</div>
            </div>
            
            <div class="bg-dark rounded-xl border border-slate-800 p-5">
                <div class="text-slate-400 text-xs font-medium mb-1">Max Drawdown (Loss Beruntun)</div>
                <div class="text-3xl font-bold text-amber-500">
                    {{ $results['max_drawdown'] }}x
                </div>
                <div class="text-xs text-slate-500 mt-2">Rangkaian loss terpanjang</div>
            </div>
        </div>
        
        @if($results['total_trades'] > 0)
            <div class="bg-dark rounded-xl border border-slate-800 overflow-hidden shadow-2xl mt-6">
                <div class="px-6 py-4 border-b border-slate-800">
                    <h3 class="text-lg font-bold text-white">Riwayat Simulasi Trade</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-800/50 text-xs uppercase font-medium text-slate-400 border-b border-slate-800">
                            <tr>
                                <th scope="col" class="px-6 py-3">Waktu Masuk</th>
                                <th scope="col" class="px-6 py-3">Tipe</th>
                                <th scope="col" class="px-6 py-3">Entry</th>
                                <th scope="col" class="px-6 py-3">Waktu Keluar</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                                <th scope="col" class="px-6 py-3">Hasil (R)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($results['trades'] as $trade)
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-6 py-3 font-mono text-xs">{{ $trade['entry_time'] }}</td>
                                <td class="px-6 py-3">
                                    <span class="{{ $trade['type'] == 'BUY' ? 'text-green-400' : 'text-red-400' }} font-bold">{{ $trade['type'] }}</span>
                                </td>
                                <td class="px-6 py-3 font-mono">{{ $trade['entry_price'] }}</td>
                                <td class="px-6 py-3 font-mono text-xs">{{ $trade['exit_time'] }}</td>
                                <td class="px-6 py-3">
                                    @if($trade['status'] == 'WIN')
                                        <span class="bg-green-500/10 text-green-400 text-xs px-2 py-1 rounded border border-green-500/20">WIN (TP Hit)</span>
                                    @else
                                        <span class="bg-red-500/10 text-red-400 text-xs px-2 py-1 rounded border border-red-500/20">LOSS (SL Hit)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 font-bold {{ $trade['pnl_r'] > 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $trade['pnl_r'] > 0 ? '+' : '' }}{{ $trade['pnl_r'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-dark rounded-xl border border-slate-800 p-12 text-center mt-6">
                <p class="text-slate-400">Tidak ada cukup data historis untuk timeframe ini atau tidak ada setup yang ditemukan.</p>
            </div>
        @endif
    @else
        <div class="bg-dark rounded-xl border border-slate-800 p-12 text-center flex flex-col items-center justify-center">
            <svg class="w-16 h-16 text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            <h3 class="text-lg font-medium text-white mb-1">Siap untuk Simulasi</h3>
            <p class="text-slate-400 max-w-md mx-auto">Pilih instrumen dan timeframe di atas, lalu klik Jalankan Simulasi untuk melihat rekam jejak performa algoritma Scalping.</p>
        </div>
    @endif
</div>
