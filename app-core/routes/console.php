<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Auto-sync data pasar setiap 5 menit
Schedule::command('smc:sync-data')->everyFiveMinutes()->withoutOverlapping();

// Auto-analyze setelah sync selesai, setiap 5 menit (offset 1 menit agar tidak bentrok)
Schedule::command('smc:analyze')->everyFiveMinutes()->withoutOverlapping();
