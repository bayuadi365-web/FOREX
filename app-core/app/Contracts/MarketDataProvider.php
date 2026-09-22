<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface MarketDataProvider
 * 
 * Abstraksi untuk sumber data harga (Twelve Data, Polygon, MetaTrader, Mock, dll).
 */
interface MarketDataProvider
{
    /**
     * Mengambil data historical (OHLCV) untuk sebuah pair dan timeframe.
     *
     * @param string $symbol Contoh: 'EURUSD'
     * @param string $timeframe Contoh: 'M15', 'H1'
     * @param int $limit Jumlah candle yang diambil
     * @return Collection Collection dari array/object data candle.
     *                    Struktur minimal: ['open_time', 'open', 'high', 'low', 'close', 'volume']
     */
    public function getHistoricalData(string $symbol, string $timeframe, int $limit = 500): Collection;
    
    /**
     * Subscribe ke realtime data jika provider mendukung WebSockets.
     * (Untuk pengembangan lanjut, bukan fokus utama saat ini)
     */
    public function subscribe(string $symbol, callable $onMessage): void;
}
