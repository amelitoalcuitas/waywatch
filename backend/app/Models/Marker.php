<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marker extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'address',
        'category',
        'description',
        'likes',
        'dislikes',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'expires_at' => 'datetime',
        ];
    }

    public const CATEGORIES = [
        'road_repair',
        'accident',
        'traffic',
        'flood',
        'hazard',
        'checkpoint',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(MarkerImage::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MarkerVote::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MarkerReport::class);
    }
}
