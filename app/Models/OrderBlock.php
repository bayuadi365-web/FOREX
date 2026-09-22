<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'pair_id', 'timeframe', 'direction', 'top', 'bottom', 'status', 'mitigated_at'
    ];

    protected $casts = [
        'mitigated_at' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }

    public function scopeFresh($query)
    {
        return $query->where('status', 'fresh');
    }
}
