<?php

namespace Tests\Feature\Orders;

use App\Models\FoodTruck;
use App\Models\Menu;
use App\Models\MenuChoice;
use App\Models\MenuOptionGroup;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerOrderOptionSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->recreateSchema();
    }

    private function recreateSchema(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menu_choices');
        Schema::dropIfExists('menu_option_groups');
        Schema::dropIfExists('menus');
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

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('foodtruck_id');
            $table->string('name');
            $table->string('category')->default('Uncategorized');
            $table->decimal('base_price', 10, 2)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('original_image')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
        });

        Schema::create('menu_option_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->string('name');
            $table->string('selection_type')->default('single');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('menu_choices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('available');
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

    private function createMenu(FoodTruck $truck, array $overrides = []): Menu
    {
        return Menu::query()->create(array_merge([
            'foodtruck_id' => $truck->id,
            'name' => 'Chicken Burger',
            'category' => 'food',
            'base_price' => null,
            'quantity' => 50,
            'description' => 'Test menu',
            'status' => 'available',
        ], $overrides));
    }

    private function createGroup(Menu $menu, string $name, string $selectionType = 'single', int $sortOrder = 0): MenuOptionGroup
    {
        return MenuOptionGroup::query()->create([
            'menu_id' => $menu->id,
            'name' => $name,
            'selection_type' => $selectionType,
            'sort_order' => $sortOrder,
        ]);
    }

    private function createChoice(MenuOptionGroup $group, string $name, float $price, string $status = 'available', int $sortOrder = 0): MenuChoice
    {
        return MenuChoice::query()->create([
            'group_id' => $group->id,
            'name' => $name,
            'price' => $price,
            'quantity' => 50,
            'sort_order' => $sortOrder,
            'status' => $status,
        ]);
    }

    public function test_single_group_requires_exactly_one_selected_choice(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck);

        $singleGroup = $this->createGroup($menu, 'Burger Type', 'single');
        $regular = $this->createChoice($singleGroup, 'Regular Chicken', 0);
        $spicy = $this->createChoice($singleGroup, 'Spicy Chicken', 1, 'available', 1);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 1,
                'selected_choices' => [$regular->id, $spicy->id],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('exactly one option', (string) $response->json('message'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_multiple_group_allows_many_choices_and_total_uses_empty_base_price_as_zero(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck, ['base_price' => null]);

        $singleGroup = $this->createGroup($menu, 'Burger Type', 'single');
        $regular = $this->createChoice($singleGroup, 'Regular Chicken', 0);
        $this->createChoice($singleGroup, 'Spicy Chicken', 1, 'available', 1);

        $multipleGroup = $this->createGroup($menu, 'Add-ons', 'multiple', 1);
        $cheese = $this->createChoice($multipleGroup, 'Cheese', 0);
        $egg = $this->createChoice($multipleGroup, 'Egg', 1, 'available', 1);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 3,
                'selected_choices' => [$regular->id, $cheese->id, $egg->id],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $orderId = $response->json('order.id');
        $this->assertNotNull($orderId);

        $order = Order::query()->findOrFail($orderId);
        $this->assertSame(3.0, (float) $order->total);

        $item = $order->items[0];
        $this->assertSame(0.0, (float) ($item['base_price'] ?? 0));
        $this->assertSame(3.0, (float) ($item['item_total'] ?? 0));
        $this->assertCount(3, $item['selected_choices'] ?? []);

        $hasZeroPriceChoice = collect($item['selected_choices'] ?? [])->contains(
            fn ($choice) => (float) ($choice['price'] ?? -1) === 0.0
        );
        $this->assertTrue($hasZeroPriceChoice);
    }

    public function test_rejects_choice_id_that_does_not_belong_to_selected_menu(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);

        $menu = $this->createMenu($truck, ['name' => 'Chicken Burger']);
        $validGroup = $this->createGroup($menu, 'Burger Type', 'single');
        $this->createChoice($validGroup, 'Regular Chicken', 0);

        $otherMenu = $this->createMenu($truck, ['name' => 'Nasi Lemak']);
        $otherGroup = $this->createGroup($otherMenu, 'Rice Type', 'single');
        $foreignChoice = $this->createChoice($otherGroup, 'Large Rice', 2);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 1,
                'selected_choices' => [$foreignChoice->id],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('Invalid or unavailable option selected', (string) $response->json('message'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_single_group_without_selection_is_rejected(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck, ['base_price' => 8]);

        $singleGroup = $this->createGroup($menu, 'Burger Type', 'single');
        $this->createChoice($singleGroup, 'Regular Chicken', 0);
        $this->createChoice($singleGroup, 'Spicy Chicken', 1, 'available', 1);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 1,
                'selected_choices' => [],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('exactly one option', (string) $response->json('message'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_rejects_unavailable_choice_selection(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck);

        $singleGroup = $this->createGroup($menu, 'Burger Type', 'single');
        $this->createChoice($singleGroup, 'Regular Chicken', 0, 'available', 0);
        $unavailable = $this->createChoice($singleGroup, 'Spicy Chicken', 1, 'unavailable', 1);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 1,
                'selected_choices' => [$unavailable->id],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('Invalid or unavailable option selected', (string) $response->json('message'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_duplicate_selected_choice_ids_are_deduplicated_and_not_double_charged(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck, ['base_price' => 10]);

        $singleGroup = $this->createGroup($menu, 'Burger Type', 'single');
        $regular = $this->createChoice($singleGroup, 'Regular Chicken', 2);

        $multipleGroup = $this->createGroup($menu, 'Add-ons', 'multiple', 1);
        $cheese = $this->createChoice($multipleGroup, 'Cheese', 1);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 2,
                'selected_choices' => [$regular->id, $regular->id, $cheese->id, $cheese->id],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::query()->findOrFail($response->json('order.id'));
        $this->assertSame(26.0, (float) $order->total);

        $item = $order->items[0];
        $this->assertSame(26.0, (float) ($item['item_total'] ?? 0));
        $this->assertCount(2, $item['selected_choices'] ?? []);
    }

    public function test_multiple_items_total_is_sum_of_each_item_total(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);

        $menuA = $this->createMenu($truck, ['name' => 'Fries', 'base_price' => 8]);
        $menuB = $this->createMenu($truck, ['name' => 'Burger', 'base_price' => null]);

        $groupB = $this->createGroup($menuB, 'Burger Type', 'single');
        $typeB = $this->createChoice($groupB, 'Regular', 1.5);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [
                [
                    'menu_id' => $menuA->id,
                    'quantity' => 2,
                    'selected_choices' => [],
                ],
                [
                    'menu_id' => $menuB->id,
                    'quantity' => 4,
                    'selected_choices' => [$typeB->id],
                ],
            ],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::query()->findOrFail($response->json('order.id'));
        $this->assertSame(22.0, (float) $order->total);
        $this->assertCount(2, $order->items ?? []);
    }

    public function test_rejects_order_when_requested_quantity_exceeds_remaining_menu_stock(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck, [
            'name' => 'Limited Burger',
            'base_price' => 7,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 2,
                'selected_choices' => [],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('Only 1 quantity left', (string) $response->json('message'));
        $this->assertDatabaseCount('orders', 0);

        $menu->refresh();
        $this->assertSame(1, (int) $menu->quantity);
        $this->assertSame('available', $menu->status);
    }

    public function test_successful_checkout_decrements_menu_quantity_and_marks_unavailable_when_zero(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck, [
            'name' => 'Limited Fries',
            'base_price' => 8,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 2,
                'selected_choices' => [],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $menu->refresh();
        $this->assertSame(0, (int) $menu->quantity);
        $this->assertSame('unavailable', $menu->status);
    }

    public function test_successful_checkout_does_not_decrement_unlimited_menu_quantity(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $menu = $this->createMenu($truck, [
            'name' => 'Unlimited Drink',
            'base_price' => 2,
            'quantity' => null,
        ]);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [[
                'menu_id' => $menu->id,
                'quantity' => 5,
                'selected_choices' => [],
            ]],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $menu->refresh();
        $this->assertNull($menu->quantity);
        $this->assertSame('available', $menu->status);
    }

    public function test_invalid_menu_items_are_skipped_when_at_least_one_valid_item_exists(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);
        $validMenu = $this->createMenu($truck, ['name' => 'Nuggets', 'base_price' => 5]);

        $response = $this->actingAs($customer)->postJson(route('customer.place-order'), [
            'foodtruck_id' => $truck->id,
            'items' => [
                [
                    'menu_id' => 999999,
                    'quantity' => 3,
                    'selected_choices' => [],
                ],
                [
                    'menu_id' => $validMenu->id,
                    'quantity' => 2,
                    'selected_choices' => [],
                ],
            ],
            'order_type' => 'self_pickup',
            'payment_method' => 'cash',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::query()->findOrFail($response->json('order.id'));
        $this->assertCount(1, $order->items ?? []);
        $this->assertSame($validMenu->id, (int) ($order->items[0]['menu_id'] ?? 0));
        $this->assertSame(10.0, (float) $order->total);
    }
}
