<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_key', 'weight_value', 'description'
    ];
}
