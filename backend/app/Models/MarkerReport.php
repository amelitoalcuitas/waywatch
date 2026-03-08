<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkerReport extends Model
{
    protected $fillable = [
        'marker_id',
        'user_id',
        'reason',
        'details',
    ];

    public const REASONS = [
        'spam',
        'inaccurate',
        'offensive',
        'other',
    ];

    public function marker(): BelongsTo
    {
        return $this->belongsTo(Marker::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
