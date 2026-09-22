<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pair extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol', 'base_currency', 'quote_currency', 
        'pip_digit', 'pip_value', 'average_spread', 'is_active'
    ];

    public function candles(): HasMany
    {
        return $this->hasMany(Candle::class);
    }

    public function marketStructures(): HasMany
    {
        return $this->hasMany(MarketStructure::class);
    }

    public function orderBlocks(): HasMany
    {
        return $this->hasMany(OrderBlock::class);
    }

    public function fairValueGaps(): HasMany
    {
        return $this->hasMany(FairValueGap::class);
    }

    public function liquidityLevels(): HasMany
    {
        return $this->hasMany(LiquidityLevel::class);
    }

    public function analysisReports(): HasMany
    {
        return $this->hasMany(AnalysisReport::class);
    }
}
