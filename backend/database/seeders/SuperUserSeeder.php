<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperUserSeeder extends Seeder
{
    /**
     * Seed the super user (admin@admin.com / Password2100!).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => 'Password2100!',
            ]
        );
    }
}
