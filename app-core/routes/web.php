<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Scalper;
use App\Livewire\Backtester;

Route::get('/', Dashboard::class);
Route::get('/scalper', Scalper::class);
Route::get('/backtest', Backtester::class);

