<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\MarketDataProvider;
use App\Services\Providers\MockDataProvider;
// use App\Services\Providers\TwelveDataProvide; // Nantinya ketika sudah punya API key

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding Interface ke implementasi spesifik
        // Ganti dengan provider asli (TwelveData, Polygon) jika sudah siap
        $this->app->bind(MarketDataProvider::class, MockDataProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
