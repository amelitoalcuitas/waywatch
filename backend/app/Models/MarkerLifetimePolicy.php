<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkerLifetimePolicy extends Model
{
    protected $fillable = [
        'category',
        'base_lifetime_minutes',
        'min_lifetime_minutes',
        'max_lifetime_minutes',
        'still_there_extension_minutes',
        'not_there_reduction_minutes',
        'grace_period_minutes',
        'early_expiry_quorum',
        'early_expiry_not_there_ratio',
        'early_expiry_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'early_expiry_not_there_ratio' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
