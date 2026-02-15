<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the specific admin user
        User::updateOrCreate(
            ['email' => 'admin@ftos.com'],
            [
                'full_name' => 'System Admin',
                'password' => Hash::make('truckadmin111_'),
                'email_verified_at' => now(),
            ]
        );
    }
}