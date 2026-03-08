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

        $disk = config('filesystems.default');
        $storageDisk = ($disk === 's3') ? 's3' : 'public';

        // For local/public storage, always emit host-agnostic URLs so
        // localhost, LAN, and ngrok all resolve against the current origin.
        if ($storageDisk === 'public') {
            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                $urlPath = parse_url($value, PHP_URL_PATH);
                if (is_string($urlPath) && str_starts_with($urlPath, '/storage/')) {
                    $relativePath = ltrim(substr($urlPath, strlen('/storage/')), '/');
                    return '/storage/'.$relativePath;
                }

                return $value;
            }

            if (str_starts_with($value, '/storage/')) {
                return $value;
            }

            return '/storage/'.ltrim($value, '/');
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

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
