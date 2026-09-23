<div wire:poll.15s="loadSignals" class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">LTF Scalping Screener</h2>
            <p class="text-sm text-slate-400 mt-1">Sinyal otomatis pada M1, M5, M15 dengan presisi Limit Order (R:R dikunci secara matematis).</p>
        </div>
        <div class="text-xs text-slate-500 flex items-center gap-2 bg-dark p-2 rounded border border-slate-800">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            Auto-refresh (15s) &bull; Last update: {{ $lastUpdated }}
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-dark rounded-xl border border-slate-800 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-xs uppercase font-medium text-slate-400 border-b border-slate-800">
                    <tr>
                        <th scope="col" class="px-6 py-4">Instrumen & TF</th>
                        <th scope="col" class="px-6 py-4">Bias / Arah</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4 font-mono text-brand">Entry Limit (OTE)</th>
                        <th scope="col" class="px-6 py-4 font-mono text-red-400">Stop Loss (SL)</th>
                        <th scope="col" class="px-6 py-4 font-mono text-green-400">TP1 (1:2)</th>
                        <th scope="col" class="px-6 py-4 font-mono text-emerald-400">TP2 (1:3)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($signals as $signal)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-bold text-white">{{ $signal['symbol'] }}</div>
                            <div class="text-xs text-slate-500 bg-slate-800 inline-block px-1.5 py-0.5 rounded mt-1">{{ $signal['timeframe'] }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($signal['bias'] == 'bullish')
                                <span class="text-green-500 font-medium flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    BUY
                                </span>
                            @elseif($signal['bias'] == 'bearish')
                                <span class="text-red-500 font-medium flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                                    SELL
                                </span>
                            @else
                                <span class="text-slate-500">Ranging</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($signal['status'] == 'active')
                                <span class="bg-green-500/10 text-green-400 text-xs px-2 py-1 rounded-full font-medium border border-green-500/20">Active Zone</span>
                            @elseif($signal['status'] == 'pending')
                                <span class="bg-amber-500/10 text-amber-400 text-xs px-2 py-1 rounded-full font-medium border border-amber-500/20">Pending Limit</span>
                            @else
                                <span class="bg-slate-500/10 text-slate-400 text-xs px-2 py-1 rounded-full font-medium border border-slate-500/20">Invalid / Wait</span>
                            @endif
                            <div class="text-[10px] text-slate-500 mt-1">{{ $signal['message'] }}</div>
                        </td>
                        <td class="px-6 py-4 font-mono font-medium {{ $signal['status'] == 'active' ? 'text-white' : 'text-slate-400' }}">
                            {{ $signal['entry'] }}
                        </td>
                        <td class="px-6 py-4 font-mono text-red-400/80">
                            {{ $signal['sl'] }}
                        </td>
                        <td class="px-6 py-4 font-mono text-green-400/80">
                            {{ $signal['tp1'] }}
                        </td>
                        <td class="px-6 py-4 font-mono text-emerald-400/80">
                            {{ $signal['tp2'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            Belum ada sinyal scalping. Menunggu struktur pasar terbentuk pada LTF.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
