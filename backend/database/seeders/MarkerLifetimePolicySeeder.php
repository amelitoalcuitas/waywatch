<?php

namespace Database\Seeders;

use App\Models\MarkerLifetimePolicy;
use Illuminate\Database\Seeder;

class MarkerLifetimePolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $default = [
            'base_lifetime_minutes' => 24 * 60,
            'min_lifetime_minutes' => 60,
            'max_lifetime_minutes' => 7 * 24 * 60,
            'still_there_extension_minutes' => 30,
            'not_there_reduction_minutes' => 45,
            'grace_period_minutes' => 30,
            'early_expiry_quorum' => 5,
            'early_expiry_not_there_ratio' => 0.8,
            'early_expiry_minutes' => 0,
            'is_active' => true,
        ];

        MarkerLifetimePolicy::updateOrCreate(
            ['category' => null],
            $default
        );

        $categoryOverrides = [
            'accident' => ['base_lifetime_minutes' => 6 * 60],
            'traffic' => ['base_lifetime_minutes' => 4 * 60],
            'flood' => ['base_lifetime_minutes' => 24 * 60],
            'hazard' => ['base_lifetime_minutes' => 18 * 60],
            'checkpoint' => ['base_lifetime_minutes' => 48 * 60],
            'road_repair' => ['base_lifetime_minutes' => 72 * 60],
        ];

        foreach ($categoryOverrides as $category => $override) {
            MarkerLifetimePolicy::updateOrCreate(
                ['category' => $category],
                array_merge($default, $override)
            );
        }
    }
}
