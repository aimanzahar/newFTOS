<?php

namespace Tests\Feature\Admin;

use App\Models\FoodTruck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckApprovalOperationalDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_truck_stays_offline_after_approval_until_ftadmin_toggles_it_online(): void
    {
        $admin = User::factory()->createOne([
            'role' => User::ROLE_SYSTEM_ADMIN,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $owner = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_ADMIN,
            'status' => 'pending',
            'email_verified_at' => now(),
        ]);

        $truck = FoodTruck::query()->create([
            'foodtruck_name' => 'Approval Flow Truck',
            'business_license_no' => 'BL-APPROVAL-001',
            'foodtruck_desc' => 'Pending truck in approval flow test',
            'user_id' => $owner->id,
            'status' => 'pending',
            'is_operational' => true,
        ]);

        $owner->update(['foodtruck_id' => $truck->id]);

        $this->actingAs(User::query()->findOrFail($admin->id))
            ->from(route('admin.pending.trucks'))
            ->post(route('admin.approve-truck', $truck->id))
            ->assertRedirect(route('admin.pending.trucks'));

        $truck->refresh();
        $owner->refresh();

        $this->assertSame('approved', $truck->status);
        $this->assertFalse((bool) $truck->is_operational);
        $this->assertSame('active', $owner->status);

        $this->actingAs(User::query()->findOrFail($owner->id))
            ->get(route('ftadmin.dashboard'))
            ->assertOk()
            ->assertViewHas('isOperational', false);

        $this->actingAs(User::query()->findOrFail($owner->id))
            ->post(route('ftadmin.toggle-operational'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_operational' => true,
            ]);

        $this->assertDatabaseHas('food_trucks', [
            'id' => $truck->id,
            'is_operational' => true,
        ]);
    }
}
