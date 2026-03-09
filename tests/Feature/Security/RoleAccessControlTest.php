<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    private function makeUser(int $role, string $status = 'active'): User
    {
        return new User([
            'full_name' => 'Security Test User',
            'email' => 'security+' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => $status,
            'email_verified_at' => now(),
        ]);
    }

    public function test_guest_cannot_access_protected_role_pages(): void
    {
        $protectedUrls = [
            '/customer/dashboard',
            '/ftadmin/orders',
            '/ftworker/dashboard',
            '/admin/global-menus',
            '/profile',
        ];

        foreach ($protectedUrls as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_customer_cannot_access_admin_ftadmin_ftworker_and_orders_pages(): void
    {
        $customer = $this->makeUser(1);

        $this->actingAs($customer)->get('/admin/global-menus')->assertRedirect('/customer/dashboard');
        $this->actingAs($customer)->get('/ftadmin/orders')->assertRedirect('/customer/dashboard');
        $this->actingAs($customer)->get('/ftworker/dashboard')->assertRedirect('/customer/dashboard');
    }

    public function test_ftadmin_cannot_access_customer_ftworker_and_admin_pages(): void
    {
        $ftadmin = $this->makeUser(2, 'active');

        $this->actingAs($ftadmin)->get('/customer/dashboard')->assertRedirect('/ftadmin/dashboard');
        $this->actingAs($ftadmin)->get('/ftworker/dashboard')->assertRedirect('/ftadmin/dashboard');
        $this->actingAs($ftadmin)->get('/admin/global-menus')->assertRedirect('/ftadmin/dashboard');
    }

    public function test_ftworker_cannot_access_customer_ftadmin_and_admin_pages(): void
    {
        $ftworker = $this->makeUser(3, 'active');

        $this->actingAs($ftworker)->get('/customer/dashboard')->assertRedirect('/ftworker/dashboard');
        $this->actingAs($ftworker)->get('/ftadmin/orders')->assertRedirect('/ftworker/dashboard');
        $this->actingAs($ftworker)->get('/admin/global-menus')->assertRedirect('/ftworker/dashboard');
    }

    public function test_admin_cannot_access_customer_ftadmin_ftworker_and_orders_pages(): void
    {
        $admin = $this->makeUser(6, 'active');

        $this->actingAs($admin)->get('/customer/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs($admin)->get('/ftadmin/orders')->assertRedirect('/admin/dashboard');
        $this->actingAs($admin)->get('/ftworker/dashboard')->assertRedirect('/admin/dashboard');
    }

    public function test_pending_ftadmin_is_blocked_from_other_urls_and_forced_to_ftadmin_dashboard(): void
    {
        $pendingFtadmin = $this->makeUser(2, 'pending');

        $this->actingAs($pendingFtadmin)->get('/admin/global-menus')->assertRedirect('/ftadmin/dashboard');
        $this->actingAs($pendingFtadmin)->get('/customer/dashboard')->assertRedirect('/ftadmin/dashboard');
        $this->actingAs($pendingFtadmin)->get('/ftworker/dashboard')->assertRedirect('/ftadmin/dashboard');
    }

    public function test_rejected_ftadmin_is_blocked_from_other_urls_and_forced_to_ftadmin_dashboard(): void
    {
        $rejectedFtadmin = $this->makeUser(2, 'rejected');

        $this->actingAs($rejectedFtadmin)->get('/admin/global-menus')->assertRedirect('/ftadmin/dashboard');
        $this->actingAs($rejectedFtadmin)->get('/customer/dashboard')->assertRedirect('/ftadmin/dashboard');
        $this->actingAs($rejectedFtadmin)->get('/ftworker/dashboard')->assertRedirect('/ftadmin/dashboard');
    }
}
