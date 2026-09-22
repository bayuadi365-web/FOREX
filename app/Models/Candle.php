<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candle extends Model
{
    use HasFactory;

    protected $fillable = [
        'pair_id', 'timeframe', 'open_time', 'open', 'high', 'low', 'close', 'volume'
    ];

    protected $casts = [
        'open_time' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }
    
    public function scopeByTimeframe($query, $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function scopeRecent($query, $limit = 100)
    {
        return $query->orderBy('open_time', 'desc')->limit($limit);
    }
}
