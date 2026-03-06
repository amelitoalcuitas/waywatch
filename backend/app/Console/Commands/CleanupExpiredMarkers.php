<?php

namespace App\Console\Commands;

use App\Models\Marker;
use Illuminate\Console\Command;

class CleanupExpiredMarkers extends Command
{
    protected $signature = 'markers:cleanup';

    protected $description = 'Delete expired markers and their associated images and votes';

    public function handle(): int
    {
        $expired = Marker::where('expires_at', '<', now())->get();
        $count = $expired->count();

        foreach ($expired as $marker) {
            $marker->images()->delete();
            $marker->votes()->delete();
            $marker->delete();
        }

        $this->info("Cleaned up {$count} expired marker(s).");

        return self::SUCCESS;
    }
}
