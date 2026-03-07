<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MarkerImage extends Model
{
    protected $fillable = [
        'marker_id',
        'image_url',
    ];

    public function getImageUrlAttribute(?string $value): string
    {
        if (! $value) {
            return '';
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $disk = config('filesystems.default');
        $storageDisk = ($disk === 's3') ? 's3' : 'public';

        return Storage::disk($storageDisk)->url($value);
    }

    public function getStoragePath(): string
    {
        return $this->attributes['image_url'] ?? '';
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(Marker::class);
    }
}
