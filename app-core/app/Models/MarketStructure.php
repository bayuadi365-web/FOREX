<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'pair_id', 'timeframe', 'type', 'direction', 'price', 'detected_at', 'is_swing'
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'is_swing' => 'boolean',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }

    public function scopeMajorSwings($query)
    {
        return $query->where('is_swing', true);
    }
}
