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
            ['email' => 'AdminSystem1@ftos.com'],
            [
                'full_name' => 'System Admin',
                'password' => Hash::make('truckadmin111_'),
                'role' => User::ROLE_SYSTEM_ADMIN,
                'phone_no' => '000000000',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@ftos.com'],
            [
                'full_name' => 'Test Customer',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_CUSTOMER,
                'phone_no' => '111111111',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'ftadmin@ftos.com'],
            [
                'full_name' => 'Test FT Admin',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_FOOD_TRUCK_ADMIN,
                'phone_no' => '222222222',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'ftworker@ftos.com'],
            [
                'full_name' => 'Test FT Worker',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_FOOD_TRUCK_WORKER,
                'phone_no' => '333333333',
                'email_verified_at' => now(),
            ]
        );
    }
}