<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\MarketDataProvider;
use App\Services\Providers\MockDataProvider;
use App\Services\Providers\YahooFinanceDataProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding Interface ke implementasi spesifik
        $this->app->bind(MarketDataProvider::class, YahooFinanceDataProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
