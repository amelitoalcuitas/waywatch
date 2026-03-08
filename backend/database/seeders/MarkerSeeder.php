<?php

namespace Database\Seeders;

use App\Models\Marker;
use App\Models\User;
use App\Services\MarkerLifetimeService;
use Illuminate\Database\Seeder;

class MarkerSeeder extends Seeder
{
    public function run(): void
    {
        $lifetimeService = app(MarkerLifetimeService::class);

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
            $policy = $lifetimeService->resolvePolicy($data['category']);
            $lifetime = $lifetimeService->buildInitialLifetime($policy);

            Marker::create([
                ...$data,
                'user_id' => $user->id,
                'expires_at' => $lifetime['expires_at'],
                'base_expires_at' => $lifetime['base_expires_at'],
                'max_expires_at' => $lifetime['max_expires_at'],
                'policy_snapshot' => $policy,
            ]);
        }
    }
}
