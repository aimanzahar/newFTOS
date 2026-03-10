<?php

use App\Models\FoodTruck;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('e2e:seed-customer-flow', function () {
    DB::transaction(function () {
        $owner = User::query()->updateOrCreate(
            ['email' => 'e2e.owner@ftos.test'],
            [
                'full_name' => 'E2E Owner',
                'password' => Hash::make('E2ePassword123!'),
                'phone_no' => '0123000001',
                'role' => User::ROLE_FOOD_TRUCK_ADMIN,
                'status' => 'active',
                'status_locked_by_system_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $ownerTwo = User::query()->updateOrCreate(
            ['email' => 'e2e.owner2@ftos.test'],
            [
                'full_name' => 'E2E Owner Two',
                'password' => Hash::make('E2ePassword123!'),
                'phone_no' => '0123000003',
                'role' => User::ROLE_FOOD_TRUCK_ADMIN,
                'status' => 'active',
                'status_locked_by_system_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $customer = User::query()->updateOrCreate(
            ['email' => 'e2e.customer@ftos.test'],
            [
                'full_name' => 'E2E Customer',
                'password' => Hash::make('E2ePassword123!'),
                'phone_no' => '0123000002',
                'role' => User::ROLE_CUSTOMER,
                'foodtruck_id' => null,
                'status' => 'active',
                'status_locked_by_system_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $truck = FoodTruck::query()->updateOrCreate(
            ['business_license_no' => 'E2E-TRUCK-001'],
            [
                'foodtruck_name' => 'E2E UX Truck',
                'foodtruck_desc' => 'Fixture truck for browser flow and pricing checks',
                'user_id' => $owner->id,
                'status' => 'approved',
                'is_operational' => true,
            ]
        );

        $truckTwo = FoodTruck::query()->updateOrCreate(
            ['business_license_no' => 'E2E-TRUCK-002'],
            [
                'foodtruck_name' => 'E2E Sunset Truck',
                'foodtruck_desc' => 'Second fixture truck for grouped multi-truck cart checks',
                'user_id' => $ownerTwo->id,
                'status' => 'approved',
                'is_operational' => true,
            ]
        );

        $owner->update(['foodtruck_id' => $truck->id]);
        $ownerTwo->update(['foodtruck_id' => $truckTwo->id]);

        $menusToReset = Menu::query()
            ->where('foodtruck_id', $truck->id)
            ->whereIn('name', ['E2E Chicken Burger', 'E2E Fries'])
            ->get();

        foreach ($menusToReset as $menu) {
            $menu->optionGroups()->delete();
            $menu->delete();
        }

        $truckTwoMenusToReset = Menu::query()
            ->where('foodtruck_id', $truckTwo->id)
            ->whereIn('name', ['E2E Wrap'])
            ->get();

        foreach ($truckTwoMenusToReset as $menu) {
            $menu->optionGroups()->delete();
            $menu->delete();
        }

        $burger = Menu::query()->create([
            'foodtruck_id' => $truck->id,
            'name' => 'E2E Chicken Burger',
            'category' => 'food',
            'base_price' => null,
            'quantity' => 200,
            'description' => 'Browser e2e menu with single + multiple option groups',
            'status' => 'available',
        ]);

        $burgerType = $burger->optionGroups()->create([
            'name' => 'Burger Type',
            'selection_type' => 'single',
            'sort_order' => 0,
        ]);

        $burgerType->choices()->create([
            'name' => 'Regular Chicken',
            'price' => 0,
            'quantity' => 200,
            'sort_order' => 0,
            'status' => 'available',
        ]);

        $burgerType->choices()->create([
            'name' => 'Spicy Chicken',
            'price' => 1,
            'quantity' => 200,
            'sort_order' => 1,
            'status' => 'available',
        ]);

        $addons = $burger->optionGroups()->create([
            'name' => 'Add-ons',
            'selection_type' => 'multiple',
            'sort_order' => 1,
        ]);

        $addons->choices()->create([
            'name' => 'Cheese',
            'price' => 0,
            'quantity' => 200,
            'sort_order' => 0,
            'status' => 'available',
        ]);

        $addons->choices()->create([
            'name' => 'Egg',
            'price' => 1,
            'quantity' => 200,
            'sort_order' => 1,
            'status' => 'available',
        ]);

        Menu::query()->create([
            'foodtruck_id' => $truck->id,
            'name' => 'E2E Fries',
            'category' => 'food',
            'base_price' => 8,
            'quantity' => 200,
            'description' => 'Flat-price menu for multi-item sum checks',
            'status' => 'available',
        ]);

        Menu::query()->create([
            'foodtruck_id' => $truckTwo->id,
            'name' => 'E2E Wrap',
            'category' => 'food',
            'base_price' => 6,
            'quantity' => 200,
            'description' => 'Second truck menu for grouped multi-truck cart checks',
            'status' => 'available',
        ]);
    });

    $this->info('E2E fixture data is ready.');
    $this->line('Customer login: e2e.customer@ftos.test / E2ePassword123!');
    $this->line('Fixture trucks: E2E UX Truck, E2E Sunset Truck');
})->purpose('Seed deterministic data for customer browser pricing and cart flow tests');
