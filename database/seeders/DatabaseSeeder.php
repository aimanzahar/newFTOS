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
        User::updateOrCreate(
            ['email' => 'admin@ftos.com'],
            [
                'full_name' => 'System Admin',
                'password' => Hash::make('truckadmin111_'),
                'role' => User::ROLE_SYSTEM_ADMIN, 
                'phone_no' => '000000000',
                'email_verified_at' => now(),
            ]
        );
    }
}