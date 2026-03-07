<?php

namespace App\Console\Commands;

use App\Models\Marker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredMarkers extends Command
{
    protected $signature = 'markers:cleanup';

    protected $description = 'Delete expired markers and their associated images and votes';

    public function handle(): int
    {
        $expired = Marker::where('expires_at', '<', now())->get();
        $count = $expired->count();

        $disk = config('filesystems.default');
        $storageDisk = ($disk === 's3') ? 's3' : 'public';

        foreach ($expired as $marker) {
            foreach ($marker->images as $image) {
                $path = $image->getStoragePath();
                if (str_starts_with($path, 'markers/')) {
                    Storage::disk($storageDisk)->delete($path);
                }
            }
            $marker->images()->delete();
            $marker->votes()->delete();
            $marker->delete();
        }

        $this->info("Cleaned up {$count} expired marker(s).");

        return self::SUCCESS;
    }
}
