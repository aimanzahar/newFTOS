<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->createOne([
            'role' => 1,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.dashboard', absolute: false));
    }

    public function test_food_truck_admin_redirects_to_ftadmin_dashboard_after_login(): void
    {
        $user = User::factory()->createOne([
            'role' => 2,
            'status' => 'active',
            'foodtruck_id' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('ftadmin.dashboard', absolute: false));
    }

    public function test_food_truck_worker_redirects_to_ftworker_dashboard_after_login(): void
    {
        $user = User::factory()->createOne([
            'role' => 3,
            'status' => 'active',
            'foodtruck_id' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('ftworker.dashboard', absolute: false));
    }

    public function test_system_admin_redirects_to_admin_dashboard_after_login(): void
    {
        $user = User::factory()->createOne([
            'role' => 6,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_dashboard_route_redirects_each_role_to_its_own_dashboard(): void
    {
        $scenarios = [
            ['role' => 1, 'status' => 'active', 'target' => route('customer.dashboard', absolute: false)],
            ['role' => 2, 'status' => 'active', 'target' => route('ftadmin.dashboard', absolute: false)],
            ['role' => 3, 'status' => 'active', 'target' => route('ftworker.dashboard', absolute: false)],
            ['role' => 6, 'status' => 'active', 'target' => route('admin.dashboard', absolute: false)],
        ];

        foreach ($scenarios as $scenario) {
            $user = User::factory()->createOne([
                'role' => $scenario['role'],
                'status' => $scenario['status'],
                'foodtruck_id' => null,
            ]);

            /** @var User $user */

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertRedirect($scenario['target']);
        }
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->createOne();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->createOne();

        /** @var User $user */

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
