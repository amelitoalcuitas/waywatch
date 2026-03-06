<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkerImage extends Model
{
    protected $fillable = [
        'marker_id',
        'image_url',
    ];

    public function marker(): BelongsTo
    {
        return $this->belongsTo(Marker::class);
    }
}
