<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'signal_id', 'pips_gained', 'max_favorable_excursion', 'max_adverse_excursion', 'closed_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function signal(): BelongsTo
    {
        return $this->belongsTo(Signal::class);
    }
}
