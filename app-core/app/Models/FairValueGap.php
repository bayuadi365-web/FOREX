<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FairValueGap extends Model
{
    use HasFactory;

    protected $fillable = [
        'pair_id', 'timeframe', 'direction', 'top', 'bottom', 'fill_percentage', 'status'
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }

    public function scopeUnfilled($query)
    {
        return $query->where('status', 'unfilled');
    }
}
