<?php

namespace Tests\Feature\Orders;

use App\Models\FoodTruck;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderRollbackOnAccountRestrictionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->recreateSchema();
    }

    private function recreateSchema(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('food_trucks');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('phone_no')->nullable();
            $table->unsignedTinyInteger('role')->default(1);
            $table->unsignedBigInteger('foodtruck_id')->nullable();
            $table->string('status')->default('active');
            $table->boolean('status_locked_by_system_admin')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('food_trucks', function (Blueprint $table) {
            $table->id();
            $table->string('foodtruck_name');
            $table->string('business_license_no')->nullable();
            $table->text('foodtruck_desc')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('pending');
            $table->boolean('is_operational')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('foodtruck_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->json('items')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('order_type')->nullable();
            $table->unsignedInteger('table_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function createUser(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'full_name' => 'Test User ' . Str::random(6),
            'email' => Str::uuid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 1,
            'status' => 'active',
            'status_locked_by_system_admin' => false,
            'foodtruck_id' => null,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function createTruck(User $owner, array $overrides = []): FoodTruck
    {
        $truck = FoodTruck::query()->create(array_merge([
            'foodtruck_name' => 'Truck ' . Str::random(4),
            'business_license_no' => 'LIC-' . Str::upper(Str::random(6)),
            'foodtruck_desc' => 'Test truck',
            'user_id' => $owner->id,
            'status' => 'approved',
            'is_operational' => true,
        ], $overrides));

        $owner->update(['foodtruck_id' => $truck->id]);

        return $truck;
    }

    private function createAcceptedOrder(int $foodtruckId, int $acceptedBy, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'foodtruck_id' => $foodtruckId,
            'customer_id' => 999,
            'customer_name' => 'Customer',
            'items' => [[
                'menu_id' => 1,
                'name' => 'Burger',
                'quantity' => 1,
                'base_price' => 12,
                'selected_choices' => [],
                'item_total' => 12,
                'status' => 'pending',
            ]],
            'total' => 12,
            'status' => 'accepted',
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
            'accepted_by' => $acceptedBy,
        ], $overrides));
    }

    public function test_ftadmin_deactivating_worker_releases_only_that_workers_accepted_orders(): void
    {
        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);

        $workerA = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);
        $workerB = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $orderA = $this->createAcceptedOrder($truck->id, $workerA->id);
        $orderB = $this->createAcceptedOrder($truck->id, $workerB->id);

        $response = $this->actingAs($owner)
            ->postJson(route('ftadmin.staff.deactivate', ['id' => $workerA->id]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'deactivated',
                'released_orders_count' => 1,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderA->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderB->id,
            'status' => 'accepted',
            'accepted_by' => $workerB->id,
        ]);
    }

    public function test_ftadmin_firing_worker_releases_only_that_workers_accepted_orders(): void
    {
        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);

        $workerA = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);
        $workerB = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $orderA = $this->createAcceptedOrder($truck->id, $workerA->id);
        $orderB = $this->createAcceptedOrder($truck->id, $workerB->id);

        $response = $this->actingAs($owner)
            ->postJson(route('ftadmin.staff.fire', ['id' => $workerA->id]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'fired',
                'released_orders_count' => 1,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderA->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderB->id,
            'status' => 'accepted',
            'accepted_by' => $workerB->id,
        ]);
    }

    public function test_system_admin_deactivating_single_worker_releases_only_that_workers_orders(): void
    {
        $admin = $this->createUser(['role' => 6]);

        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);

        $workerA = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);
        $workerB = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $otherOwner = $this->createUser(['role' => 2]);
        $otherTruck = $this->createTruck($otherOwner);
        $otherWorker = $this->createUser(['role' => 3, 'foodtruck_id' => $otherTruck->id]);

        $orderA = $this->createAcceptedOrder($truck->id, $workerA->id);
        $orderB = $this->createAcceptedOrder($truck->id, $workerB->id);
        $orderC = $this->createAcceptedOrder($otherTruck->id, $otherWorker->id);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.truck-user.update-status', ['truckId' => $truck->id, 'userId' => $workerA->id]),
            [
                'status' => 'deactivated',
                'target_type' => 'staff',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'deactivated',
                'status_locked_by_system_admin' => true,
                'released_orders_count' => 1,
                'cascaded_to_workers' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerA->id,
            'status' => 'deactivated',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerB->id,
            'status' => 'active',
            'status_locked_by_system_admin' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'status' => 'active',
            'status_locked_by_system_admin' => 0,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderA->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderB->id,
            'status' => 'accepted',
            'accepted_by' => $workerB->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderC->id,
            'status' => 'accepted',
            'accepted_by' => $otherWorker->id,
        ]);
    }

    public function test_system_admin_firing_single_worker_releases_only_that_workers_orders(): void
    {
        $admin = $this->createUser(['role' => 6]);

        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);

        $workerA = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);
        $workerB = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $otherOwner = $this->createUser(['role' => 2]);
        $otherTruck = $this->createTruck($otherOwner);
        $otherWorker = $this->createUser(['role' => 3, 'foodtruck_id' => $otherTruck->id]);

        $orderA = $this->createAcceptedOrder($truck->id, $workerA->id);
        $orderB = $this->createAcceptedOrder($truck->id, $workerB->id);
        $orderC = $this->createAcceptedOrder($otherTruck->id, $otherWorker->id);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.truck-user.update-status', ['truckId' => $truck->id, 'userId' => $workerA->id]),
            [
                'status' => 'fired',
                'target_type' => 'staff',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'fired',
                'status_locked_by_system_admin' => true,
                'released_orders_count' => 1,
                'cascaded_to_workers' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerA->id,
            'status' => 'fired',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerB->id,
            'status' => 'active',
            'status_locked_by_system_admin' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'status' => 'active',
            'status_locked_by_system_admin' => 0,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderA->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderB->id,
            'status' => 'accepted',
            'accepted_by' => $workerB->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderC->id,
            'status' => 'accepted',
            'accepted_by' => $otherWorker->id,
        ]);
    }

    public function test_system_admin_deactivating_owner_cascades_workers_and_rejects_truck_active_orders(): void
    {
        $admin = $this->createUser(['role' => 6]);

        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);

        $workerA = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);
        $workerB = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $otherOwner = $this->createUser(['role' => 2]);
        $otherTruck = $this->createTruck($otherOwner);
        $otherWorker = $this->createUser(['role' => 3, 'foodtruck_id' => $otherTruck->id]);

        $orderA = $this->createAcceptedOrder($truck->id, $workerA->id);
        $orderB = $this->createAcceptedOrder($truck->id, $workerB->id, [
            'status' => 'preparing',
        ]);
        $orderPending = $this->createAcceptedOrder($truck->id, $workerA->id, [
            'status' => 'pending',
            'accepted_by' => null,
        ]);
        $orderC = $this->createAcceptedOrder($otherTruck->id, $otherWorker->id);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.truck-user.update-status', ['truckId' => $truck->id, 'userId' => $owner->id]),
            [
                'status' => 'deactivated',
                'target_type' => 'owner',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'deactivated',
                'status_locked_by_system_admin' => true,
                'cascaded_to_workers' => true,
                'cascaded_workers_count' => 2,
                'released_orders_count' => 3,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'status' => 'deactivated',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerA->id,
            'status' => 'deactivated',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerB->id,
            'status' => 'deactivated',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $otherWorker->id,
            'status' => 'active',
            'status_locked_by_system_admin' => 0,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderA->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderB->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderPending->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderC->id,
            'status' => 'accepted',
            'accepted_by' => $otherWorker->id,
        ]);
    }

    public function test_system_admin_deactivating_owner_without_workers_rejects_owners_active_orders(): void
    {
        $admin = $this->createUser(['role' => 6]);

        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);

        $acceptedOrder = $this->createAcceptedOrder($truck->id, $owner->id, [
            'status' => 'accepted',
        ]);

        $inProgressOrder = $this->createAcceptedOrder($truck->id, $owner->id, [
            'status' => 'delivery',
        ]);

        $pendingOrder = $this->createAcceptedOrder($truck->id, $owner->id, [
            'status' => 'pending',
            'accepted_by' => null,
        ]);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.truck-user.update-status', ['truckId' => $truck->id, 'userId' => $owner->id]),
            [
                'status' => 'deactivated',
                'target_type' => 'owner',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'cascaded_to_workers' => true,
                'cascaded_workers_count' => 0,
                'released_orders_count' => 3,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $acceptedOrder->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $inProgressOrder->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $pendingOrder->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);
    }

    public function test_system_admin_firing_owner_cascades_workers_and_rejects_truck_active_orders(): void
    {
        $admin = $this->createUser(['role' => 6]);

        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);

        $workerA = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);
        $workerB = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $otherOwner = $this->createUser(['role' => 2]);
        $otherTruck = $this->createTruck($otherOwner);
        $otherWorker = $this->createUser(['role' => 3, 'foodtruck_id' => $otherTruck->id]);

        $orderA = $this->createAcceptedOrder($truck->id, $workerA->id);
        $orderB = $this->createAcceptedOrder($truck->id, $workerB->id, [
            'status' => 'ready_for_pickup',
        ]);
        $orderPending = $this->createAcceptedOrder($truck->id, $workerA->id, [
            'status' => 'pending',
            'accepted_by' => null,
        ]);
        $orderC = $this->createAcceptedOrder($otherTruck->id, $otherWorker->id);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.truck-user.update-status', ['truckId' => $truck->id, 'userId' => $owner->id]),
            [
                'status' => 'fired',
                'target_type' => 'owner',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'fired',
                'status_locked_by_system_admin' => true,
                'cascaded_to_workers' => true,
                'cascaded_workers_count' => 2,
                'released_orders_count' => 3,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'status' => 'fired',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerA->id,
            'status' => 'fired',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $workerB->id,
            'status' => 'fired',
            'status_locked_by_system_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $otherWorker->id,
            'status' => 'active',
            'status_locked_by_system_admin' => 0,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderA->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderB->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderPending->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderC->id,
            'status' => 'accepted',
            'accepted_by' => $otherWorker->id,
        ]);
    }

    public function test_customer_dashboard_shows_order_status_returned_to_pending_after_worker_deactivation(): void
    {
        $customer = $this->createUser(['role' => 1]);
        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);
        $worker = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $order = $this->createAcceptedOrder($truck->id, $worker->id, [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
        ]);

        $orderNumber = 'Order #' . str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);

        $this->actingAs($customer)
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSeeInOrder([$orderNumber, 'Accepted']);

        $this->actingAs($owner)
            ->postJson(route('ftadmin.staff.deactivate', ['id' => $worker->id]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);

        $this->actingAs($customer)
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSeeInOrder([$orderNumber, 'Pending']);
    }

    public function test_customer_dashboard_shows_order_status_returned_to_pending_after_worker_fired(): void
    {
        $customer = $this->createUser(['role' => 1]);
        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);
        $worker = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $order = $this->createAcceptedOrder($truck->id, $worker->id, [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
        ]);

        $orderNumber = 'Order #' . str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);

        $this->actingAs($customer)
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSeeInOrder([$orderNumber, 'Accepted']);

        $this->actingAs($owner)
            ->postJson(route('ftadmin.staff.fire', ['id' => $worker->id]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'accepted_by' => null,
        ]);

        $this->actingAs($customer)
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSeeInOrder([$orderNumber, 'Pending']);
    }

    public function test_customer_dashboard_shows_rejected_refund_notices_after_system_admin_deactivates_owner(): void
    {
        $admin = $this->createUser(['role' => 6]);
        $customer = $this->createUser(['role' => 1]);
        $owner = $this->createUser(['role' => 2]);
        $truck = $this->createTruck($owner);
        $worker = $this->createUser(['role' => 3, 'foodtruck_id' => $truck->id]);

        $onlineOrder = $this->createAcceptedOrder($truck->id, $worker->id, [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'status' => 'delivery',
            'payment_method' => 'Maybank2u',
            'total' => 23.40,
            'items' => [[
                'menu_id' => 1,
                'name' => 'Burger Combo',
                'quantity' => 1,
                'base_price' => 23.40,
                'selected_choices' => [],
                'item_total' => 23.40,
                'status' => 'delivery',
            ]],
        ]);

        $cashOrder = $this->createAcceptedOrder($truck->id, $worker->id, [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'status' => 'accepted',
            'payment_method' => 'cash',
            'total' => 14.50,
            'items' => [[
                'menu_id' => 2,
                'name' => 'Fries',
                'quantity' => 1,
                'base_price' => 14.50,
                'selected_choices' => [],
                'item_total' => 14.50,
                'status' => 'accepted',
            ]],
        ]);

        $pendingPaidOrder = $this->createAcceptedOrder($truck->id, $worker->id, [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'status' => 'pending',
            'accepted_by' => null,
            'payment_method' => 'GrabPay',
            'total' => 18.20,
            'items' => [[
                'menu_id' => 3,
                'name' => 'Nuggets',
                'quantity' => 1,
                'base_price' => 18.20,
                'selected_choices' => [],
                'item_total' => 18.20,
                'status' => 'pending',
            ]],
        ]);

        $this->actingAs($admin)->patchJson(
            route('admin.truck-user.update-status', ['truckId' => $truck->id, 'userId' => $owner->id]),
            [
                'status' => 'deactivated',
                'target_type' => 'owner',
            ]
        )
            ->assertOk()
            ->assertJson([
                'success' => true,
                'cascaded_to_workers' => true,
                'released_orders_count' => 3,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $onlineOrder->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $cashOrder->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $pendingPaidOrder->id,
            'status' => 'rejected',
            'accepted_by' => null,
        ]);

        $onlineOrderNumber = 'Order #' . str_pad((string) $onlineOrder->id, 4, '0', STR_PAD_LEFT);
        $cashOrderNumber = 'Order #' . str_pad((string) $cashOrder->id, 4, '0', STR_PAD_LEFT);

        $this->actingAs($customer)
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('Refund Notice')
            ->assertSee($onlineOrderNumber)
            ->assertSee($cashOrderNumber)
            ->assertSee('Rejected')
            ->assertSee('Amount:')
            ->assertSee('Payment Method:')
            ->assertSee('RM 23.40')
            ->assertSee('RM 14.50')
            ->assertSee('Maybank2u')
            ->assertSee('Cash')
            ->assertSee('Your payment of RM 23.40 via Maybank2u will be refunded to the same payment method.')
            ->assertSee('Please show your order receipt at our food truck to receive your cash refund of RM 14.50.');
    }
}
