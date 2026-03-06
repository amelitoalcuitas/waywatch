<?php

namespace Database\Seeders;

use App\Models\Marker;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarkerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        $markers = [
            [
                'latitude' => 14.5995,
                'longitude' => 120.9842,
                'category' => 'traffic',
                'description' => 'Heavy traffic on main road',
            ],
            [
                'latitude' => 14.6000,
                'longitude' => 120.9850,
                'category' => 'road_repair',
                'description' => 'Road repair in progress',
            ],
        ];

        foreach ($markers as $data) {
            Marker::create([
                ...$data,
                'user_id' => $user->id,
                'expires_at' => now()->addWeek(),
            ]);
        }
    }
}
