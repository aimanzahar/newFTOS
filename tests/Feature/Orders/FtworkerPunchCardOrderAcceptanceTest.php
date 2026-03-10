<?php

namespace Tests\Feature\Orders;

use App\Models\FoodTruck;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkerPunchCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FtworkerPunchCardOrderAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function createTruck(User $owner): FoodTruck
    {
        $truck = FoodTruck::query()->create([
            'foodtruck_name' => 'Punch Rule Truck',
            'business_license_no' => 'BL-PUNCH-001',
            'foodtruck_desc' => 'Truck used for punch card acceptance tests',
            'user_id' => $owner->id,
            'status' => 'approved',
            'is_operational' => true,
        ]);

        $owner->update(['foodtruck_id' => $truck->id]);

        return $truck;
    }

    private function createPendingOrder(FoodTruck $truck): Order
    {
        return Order::query()->create([
            'foodtruck_id' => $truck->id,
            'customer_name' => 'Test Customer',
            'items' => [
                [
                    'name' => 'Chicken Burger',
                    'quantity' => 2,
                    'base_price' => 6.5,
                    'selected_choices' => [],
                    'item_total' => 13.0,
                ],
            ],
            'total' => 13.0,
            'status' => 'pending',
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);
    }

    public function test_ftworker_cannot_accept_order_without_active_punch_card(): void
    {
        $owner = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_ADMIN,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $truck = $this->createTruck($owner);

        $worker = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_WORKER,
            'status' => 'active',
            'foodtruck_id' => $truck->id,
            'email_verified_at' => now(),
        ]);

        $order = $this->createPendingOrder($truck);

        $response = $this->actingAs(User::query()->findOrFail($worker->id))
            ->postJson(route('orders.accept', $order->id));

        $response->assertStatus(403)->assertJson([
            'success' => false,
            'code' => 'punch_card_required',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);
    }

    public function test_ftworker_with_active_punch_card_can_accept_order(): void
    {
        $owner = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_ADMIN,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $truck = $this->createTruck($owner);

        $worker = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_WORKER,
            'status' => 'active',
            'foodtruck_id' => $truck->id,
            'email_verified_at' => now(),
        ]);

        WorkerPunchCard::query()->create([
            'user_id' => $worker->id,
            'foodtruck_id' => $truck->id,
            'punched_in_at' => now()->subHour(),
            'punched_out_at' => null,
        ]);

        $order = $this->createPendingOrder($truck);

        $response = $this->actingAs(User::query()->findOrFail($worker->id))
            ->postJson(route('orders.accept', $order->id));

        $response->assertOk()->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'accepted',
            'accepted_by' => $worker->id,
        ]);
    }

    public function test_ftadmin_can_still_accept_order_without_punch_card(): void
    {
        $owner = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_ADMIN,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $truck = $this->createTruck($owner);

        $order = $this->createPendingOrder($truck);

        $response = $this->actingAs(User::query()->findOrFail($owner->id))
            ->postJson(route('orders.accept', $order->id));

        $response->assertOk()->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'accepted',
            'accepted_by' => $owner->id,
        ]);
    }

    public function test_ftworker_cannot_accept_order_after_punching_out(): void
    {
        $owner = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_ADMIN,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $truck = $this->createTruck($owner);

        $worker = User::factory()->createOne([
            'role' => User::ROLE_FOOD_TRUCK_WORKER,
            'status' => 'active',
            'foodtruck_id' => $truck->id,
            'email_verified_at' => now(),
        ]);

        WorkerPunchCard::query()->create([
            'user_id' => $worker->id,
            'foodtruck_id' => $truck->id,
            'punched_in_at' => now()->subHours(2),
            'punched_out_at' => now()->subHour(),
        ]);

        $order = $this->createPendingOrder($truck);

        $response = $this->actingAs(User::query()->findOrFail($worker->id))
            ->postJson(route('orders.accept', $order->id));

        $response->assertStatus(403)->assertJson([
            'success' => false,
            'code' => 'punch_card_required',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);
    }
}
