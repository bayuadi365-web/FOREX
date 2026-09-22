<div wire:poll.30s="loadPairData" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    
    <!-- Left Sidebar: Pairs & Bias -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Pair Selector -->
        <div class="bg-dark border border-slate-800 rounded-xl p-5 shadow-lg">
            <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Select Instrument</h3>
            <div class="space-y-2">
                @foreach($pairs as $pair)
                    <button 
                        wire:click="selectPair({{ $pair['id'] }})"
                        class="w-full text-left px-4 py-3 rounded-lg flex items-center justify-between transition-colors {{ $selectedPairId == $pair['id'] ? 'bg-brand/10 border border-brand/50 text-white' : 'bg-slate-800/50 border border-transparent text-slate-400 hover:bg-slate-800' }}">
                        <span class="font-bold">{{ $pair['symbol'] }}</span>
                        @if($selectedPairId == $pair['id'])
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-brand"></span>
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <!-- HTF / LTF Bias Matrix -->
        <div class="bg-dark border border-slate-800 rounded-xl p-5 shadow-lg">
            <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Structure Bias</h3>
            <div class="grid grid-cols-2 gap-2">
                @foreach($biases as $tf => $bias)
                    <div class="flex items-center justify-between p-2 rounded bg-slate-800/50 border border-slate-700/50">
                        <span class="text-xs font-bold text-slate-300">{{ $tf }}</span>
                        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded 
                            {{ $bias === 'bullish' ? 'bg-green-500/20 text-green-400' : ($bias === 'bearish' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400') }}">
                            {{ $bias }}
                        </span>
                    </div>
                @endforeach
            </div>
            
            @if($biases['M30'] !== $biases['M15'] && $biases['M30'] !== 'ranging' && $biases['M15'] !== 'ranging')
                <div class="mt-4 p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20 flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-xs text-yellow-500/90 leading-relaxed">
                        <strong class="block text-yellow-500 mb-1">Konflik Struktur</strong>
                        Bias M30 dan M15 berlawanan. Probabilitas setup diturunkan.
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Main Content: Chart & Analysis -->
    <div class="lg:col-span-3 space-y-6">
        
        <!-- Score & Main Bias Card -->
        @if($currentReport)
        <div class="bg-gradient-to-r from-dark to-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-6 opacity-10 pointer-events-none">
                <svg class="w-32 h-32 text-brand" fill="currentColor" viewBox="0 0 24 24"><path d="M21 21H3v-2h18v2zm-2-12l-4-4v3H9v-3l-4 4 4 4v-3h6v3l4-4z"/></svg>
            </div>
            
            <div class="flex flex-col md:flex-row gap-6 items-start md:items-center relative z-10">
                <!-- Score Circle -->
                <div class="shrink-0 flex flex-col items-center justify-center w-28 h-28 rounded-full border-4 {{ $currentReport['score'] >= 75 ? 'border-green-500 bg-green-500/10 text-green-400' : ($currentReport['score'] >= 50 ? 'border-yellow-500 bg-yellow-500/10 text-yellow-400' : 'border-red-500 bg-red-500/10 text-red-400') }}">
                    <span class="text-4xl font-black">{{ $currentReport['score'] }}</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider mt-1 opacity-80">Score</span>
                </div>
                
                <div class="flex-1 space-y-4">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h2 class="text-2xl font-bold text-white">Skenario Utama</h2>
                            <span class="px-3 py-1 text-xs font-bold uppercase rounded-full {{ $currentReport['bias'] === 'bullish' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                {{ $currentReport['bias'] }} Bias
                            </span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">{{ $currentReport['primary_scenario']['description'] }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-darker/50 p-3 rounded border border-slate-800/50">
                            <span class="block text-xs text-slate-500 uppercase font-semibold mb-1">Target Likuiditas</span>
                            <span class="font-mono text-lg text-brand">{{ $currentReport['primary_scenario']['target_level'] }}</span>
                        </div>
                        <div class="bg-darker/50 p-3 rounded border border-slate-800/50">
                            <span class="block text-xs text-slate-500 uppercase font-semibold mb-1">Invalidation Level</span>
                            <span class="font-mono text-lg text-red-400">{{ $currentReport['alt_scenario']['invalidation_level'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- The Chart -->
        <div class="bg-dark border border-slate-800 rounded-xl p-1 shadow-lg h-[600px] flex flex-col" wire:ignore>
            <!-- Chart Container for TradingView Advanced Widget -->
            <div id="tv-chart" class="flex-1 w-full h-full"></div>
        </div>
        
        <!-- Key Levels Table -->
        <div class="bg-dark border border-slate-800 rounded-xl overflow-hidden shadow-lg">
            <div class="px-5 py-4 border-b border-slate-800 bg-darker/50">
                <h3 class="font-bold text-white">Active Point of Interests (POI)</h3>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/30 text-xs uppercase tracking-wider text-slate-400">
                        <th class="p-4 font-semibold">Tipe POI</th>
                        <th class="p-4 font-semibold">Harga</th>
                        <th class="p-4 font-semibold">Timeframe</th>
                        <th class="p-4 font-semibold">Jarak</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50 text-sm">
                    @foreach($keyLevels as $level)
                        <tr class="hover:bg-slate-800/20 transition-colors">
                            <td class="p-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ str_contains($level['type'], 'Bullish') || str_contains($level['type'], 'Buy') ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $level['type'] }}
                            </td>
                            <td class="p-4 font-mono text-slate-300">{{ $level['price'] }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-[10px] bg-slate-700 text-slate-300 rounded font-bold">{{ $level['tf'] }}</span>
                            </td>
                            <td class="p-4 text-slate-400">{{ $level['distance'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- TradingView Widget Logic -->
<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        let currentWidget = null;

        function loadTradingViewWidget(symbol) {
            // TradingView symbol format (e.g. FX:EURUSD or OANDA:EURUSD)
            let tvSymbol = "FX:" + symbol;
            
            if (symbol === 'XAUUSD') {
                tvSymbol = "OANDA:XAUUSD";
            }

            currentWidget = new TradingView.widget({
                "autosize": true,
                "symbol": tvSymbol,
                "interval": "15",
                "timezone": "Etc/UTC",
                "theme": "dark",
                "style": "1",
                "locale": "id",
                "enable_publishing": false,
                "backgroundColor": "#0f172a",
                "gridColor": "#1e293b",
                "hide_top_toolbar": false,
                "hide_legend": false,
                "save_image": false,
                "container_id": "tv-chart"
            });
        }

        // Initialize with default
        loadTradingViewWidget('EURUSD');

        // Listen for pair change
        window.addEventListener('pair-changed', event => {
            loadTradingViewWidget(event.detail.symbol);
        });
    });
</script>
