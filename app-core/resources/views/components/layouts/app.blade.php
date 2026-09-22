<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMC Radar - Professional Forex Analytics</title>
    
    <!-- Tailwind CSS (CDN for MVP) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: '#0f172a',
                        darker: '#0b1120',
                        brand: '#3b82f6',
                    }
                }
            }
        }
    </script>
    
    <!-- TradingView Lightweight Charts -->
    <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
    
    @livewireStyles
</head>
<body class="bg-darker text-slate-300 font-sans antialiased selection:bg-brand selection:text-white">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navbar -->
        <header class="bg-dark border-b border-slate-800 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-brand flex items-center justify-center font-bold text-white shadow-lg shadow-brand/20">
                            SR
                        </div>
                        <span class="font-bold text-xl tracking-tight text-white">SMC Radar</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-slate-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            Live Market Data
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }}
        </main>
        
        <!-- Footer -->
        <footer class="bg-dark border-t border-slate-800 py-6 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-slate-500">
                &copy; {{ date('Y') }} SMC Radar. This is a probabilistic analysis tool, not financial advice. Trading forex carries a high level of risk.
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
