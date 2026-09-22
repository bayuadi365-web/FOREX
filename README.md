# SMC Radar 

SMC Radar adalah platform analisa multi-timeframe (*Top-Down*) untuk pasar *forex* yang membaca struktur pasar berdasarkan *Smart Money Concept* (SMC) dan metodologi *Inner Circle Trader* (ICT). 

Sistem ini melakukan pembacaan harga secara algoritmik dan mandiri, menimbang berbagai parameter probabilistik (BOS, CHoCH, Order Block, Liquidity Sweeps, Killzones), dan menerbitkan **Skenario Proyeksi** (bukan sekadar sinyal *buy/sell*).

---

## ⚠️ Disclaimer & Peringatan Risiko
**Sistem ini adalah alat bantu analisa probabilistik, bukan mesin peramal harga.** 
Tidak ada algoritma di dunia ini yang dapat menjamin kepastian pergerakan pasar secara presisi 100%. Tingkat keberhasilan (*win rate*) dan skor probabilitas sangat bergantung pada validasi algoritma *backtesting*. Performa masa lalu tidak menjamin keuntungan di masa depan. Trading *Forex* memiliki risiko yang sangat tinggi, termasuk risiko kehilangan seluruh modal Anda. Bijaklah dalam mengambil keputusan finansial.

---

## Teknologi (Tech Stack)

SMC Radar dibangun di atas ekosistem modern Laravel 11.
* **Backend:** PHP 8.3, Laravel 11 (Service Pattern).
* **Database:** MariaDB 11.x (dilengkapi fitur partisi per bulan pada tabel historis *candle*).
* **Asynchronous & Queue:** Redis + Laravel Horizon (menangani sinkronisasi *candle* & kalkulasi berat *engine* SMC).
* **Frontend:** Livewire 3 + Alpine.js + TailwindCSS.
* **Charting:** TradingView Lightweight Charts.

---

## Arsitektur Sistem

Pilar utama logika bisnis dapat Anda temukan pada *folder* `app/Services/Smc/` yang sengaja dipecah per-konsep untuk memudahkan *maintenance* dan eksekusi *Unit Test*:

1. **`MarketStructureEngine`**: Identifikasi *fractal* penentu *Swing High/Low*, BOS (*Break of Structure*), dan CHoCH (*Change of Character*).
2. **`LiquidityEngine`**: Mencari penumpukan *Equal Highs / Equal Lows* dan peristiwa perburuan *stop-loss* (*Stop Hunts / Liquidity Sweeps*).
3. **`OrderBlockEngine`**: Memverifikasi kemunculan dan batas area *Order Blocks* (OB) yang menyebabkan impuls harga, serta memantau status *fresh/mitigated*.
4. **`FvgEngine`**: Menganalisa *Fair Value Gaps* (Imbalance) berdasarkan formasi harga 3-candle, menghitung tingkat celah *(unfilled, partial, filled)*.
5. **`PremiumDiscountEngine`**: Menghitung zona keseimbangan (*Equilibrium*) dan area diskon/premium (*Optimal Trade Entry* pada fibonacci 0.62-0.79).
6. **`TimeKillzoneEngine`**: Mengafirmasi bias pasar berdasarkan waktu sesi dunia (Asia, London, New York) dalam GMT.
7. **`ConfluenceScoringEngine`**: Agregator yang menjumlahkan konfirmasi bobot dan mengeluarkan probabilitas matriks akhir (0 - 100).
8. **`TopDownAnalysisOrchestrator`**: Orkestrator eksekutif pembaca "Konflik Struktur". Menjalankan semua rentang analisis di *Higher Timeframe* turun ke *Lower Timeframe* (D1 -> H4 -> H1 -> M30 -> M15 -> M5 -> M1).

---

## Panduan Instalasi (Development Lokal)

Proyek ini telah dikonfigurasi untuk dijalankan pada infrastruktur kontainer menggunakan Docker.

### 1. Kebutuhan Sistem
- Docker & Docker Compose
- Sistem berbasis UNIX/Linux/macOS atau Windows (menggunakan WSL2).
- Akses Port `8000` (Web), `8080` (Websockets), `3306` (MariaDB), `6379` (Redis).

### 2. Cara Eksekusi Awal (Bootstrap)

Karena folder proyek ini berisi kerangka mentah, Anda harus mem-*bootstrap* *Laravel environment* ke dalamnya jika ingin menjalankannya:

1. Unduh dan inisialisasi Laravel 11.
2. Tempatkan semua *file* dari proyek SMC ini (direktori `app/`, `database/`, `resources/`, dan `docker-compose.yml`) menimpa hasil *scaffolding* murni Laravel 11.
3. Buka *terminal/command prompt* pada *root directory* proyek, lalu jalankan:

```bash
# Salin konfigurasi environment
cp .env.example .env

# Nyalakan cluster kontainer 
docker compose up -d --build

# Buka akses console ke container `app` PHP
docker compose exec app bash
```

Di dalam kontainer (`/var/www/html`), jalankan:
```bash
# Instal seluruh package PHP
composer install

# Generate application key
php artisan key:generate

# Migrasi kerangka skema database beserta data awal pembobotan skor
php artisan migrate --seed
```

### 3. Menggunakan Sistem
Setelah berjalan:
- Dashboard (Frontend) dapat diakses di browser melalui `http://localhost:8000/`.
- Untuk menarik data *dummy candle*, jalankan instruksi scheduler di konsol: `php artisan smc:sync-data`.

---

## Peta Jalan Pengembangan (Roadmap)

Sistem ini adalah landasan kuat, namun pengembangan lebih lanjut amat diperlukan untuk membuatnya *Production-Ready*:
1. **Pest Unit Tests**: Implementasi *fixture* data *candle* tiruan untuk menguji seluruh algoritma matematika dan filter *timeframe* (BOS/CHoCH).
2. **Provider Data API (Live)**: Membangun kelas *driver* pada antarmuka `MarketDataProvider` yang mampu menyedot data asli dari Finnhub/Polygon/OANDA atau *bridge* ZeroMQ dari Metatrader 5.
3. **Backtesting Engine**: Merealisasikan kemampuan *forward/backward pass simulation* atas historis 2+ tahun untuk membuktikan probabilitas label skor.
4. **Notifikasi Otomatis**: Integrasi *bot webhook* (Telegram/Discord) untuk menge-ping trader ketika sistem menemukan *Skenario High Probability* tanpa konflik.
5. **Filament 3 Admin Panel**: Panel dinamis untuk pengelolaan CRUD pada bobot *Scoring Engine* dan konfigurasi toleransi algoritma.
