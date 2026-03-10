<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 1,
            'phone_no' => '0123456789',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.dashboard', absolute: false));
    }

    public function test_food_truck_admin_registration_redirects_to_ftadmin_dashboard(): void
    {
        $response = $this->post('/register', [
            'full_name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 2,
            'phone_no' => '0123456790',
            'foodtruck_name' => 'Owner Truck',
            'business_license_no' => 'BL-123456',
            'foodtruck_desc' => 'Owner test truck',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('ftadmin.dashboard', absolute: false));

        $this->assertDatabaseHas('food_trucks', [
            'foodtruck_name' => 'Owner Truck',
            'business_license_no' => 'BL-123456',
            'status' => 'pending',
        ]);

        $owner = User::query()->where('email', 'owner@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertNotNull($owner->foodtruck_id);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'role' => 2,
            'foodtruck_id' => $owner->foodtruck_id,
        ]);
    }
}
