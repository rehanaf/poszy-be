<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSetting extends Model
{
    use HasStore;

    protected $fillable = [
        'earn_min_amount',
        'earn_points',
        'earn_multiple',
        'exchange_points',
        'exchange_discount_value',
        'exchange_discount_type',
        'store_id',
    ];

    protected $casts = [
        'earn_multiple' => 'boolean',
    ];
}