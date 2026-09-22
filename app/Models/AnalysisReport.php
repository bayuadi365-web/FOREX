<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'pair_id', 'bias', 'score', 'primary_scenario', 'alt_scenario', 'generated_at'
    ];

    protected $casts = [
        'primary_scenario' => 'array',
        'alt_scenario' => 'array',
        'generated_at' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class, 'report_id');
    }
}
