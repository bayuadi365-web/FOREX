<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Signal extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id', 'entry', 'sl', 'tp1', 'tp2', 'tp3', 'rr', 'confidence', 'status'
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(AnalysisReport::class, 'report_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(SignalResult::class);
    }
}
