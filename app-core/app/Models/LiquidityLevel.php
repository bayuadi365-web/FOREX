<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidityLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'pair_id', 'timeframe', 'type', 'price', 'status', 'swept_at'
    ];

    protected $casts = [
        'swept_at' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }

    public function scopeIntact($query)
    {
        return $query->where('status', 'intact');
    }
}
