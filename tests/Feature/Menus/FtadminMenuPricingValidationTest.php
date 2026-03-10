<?php

namespace Tests\Feature\Menus;

use App\Models\FoodTruck;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class FtadminMenuPricingValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->recreateSchema();
    }

    private function recreateSchema(): void
    {
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
    }

    private function createUser(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'full_name' => 'Test User ' . Str::random(6),
            'email' => Str::uuid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_FOOD_TRUCK_ADMIN,
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

    public function test_allows_empty_base_price_when_named_choices_have_numeric_prices_including_zero(): void
    {
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);

        $payload = [
            'name' => 'Chicken Burger',
            'category' => 'food',
            'base_price' => null,
            'quantity' => 20,
            'description' => 'test',
            'option_groups' => json_encode([
                [
                    'name' => 'Burger Type',
                    'selectionType' => 'single',
                    'choices' => [
                        ['name' => 'Regular Chicken', 'price' => 0, 'quantity' => 10],
                        ['name' => 'Spicy Chicken', 'price' => 1.5, 'quantity' => 10],
                    ],
                ],
            ]),
        ];

        $response = $this->actingAs($owner)
            ->post(route('ftadmin.menu.store'), $payload, ['Accept' => 'application/json']);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('menus', [
            'foodtruck_id' => $truck->id,
            'name' => 'Chicken Burger',
        ]);

        $this->assertDatabaseHas('menu_choices', [
            'name' => 'Regular Chicken',
            'price' => 0,
        ]);
    }

    public function test_rejects_when_base_price_empty_and_named_choice_has_missing_price(): void
    {
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $this->createTruck($owner);

        $payload = [
            'name' => 'Chicken Burger',
            'category' => 'food',
            'base_price' => null,
            'quantity' => 20,
            'description' => 'test',
            'option_groups' => json_encode([
                [
                    'name' => 'Burger Type',
                    'selectionType' => 'single',
                    'choices' => [
                        ['name' => 'Regular Chicken', 'price' => '', 'quantity' => 10],
                    ],
                ],
            ]),
        ];

        $response = $this->actingAs($owner)
            ->post(route('ftadmin.menu.store'), $payload, ['Accept' => 'application/json']);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('Please provide pricing', (string) $response->json('message'));
        $this->assertDatabaseCount('menus', 0);
    }

    public function test_rejects_when_base_price_empty_and_no_named_choices_exist(): void
    {
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $this->createTruck($owner);

        $payload = [
            'name' => 'Chicken Burger',
            'category' => 'food',
            'base_price' => null,
            'quantity' => 20,
            'description' => 'test',
            'option_groups' => json_encode([
                [
                    'name' => 'Burger Type',
                    'selectionType' => 'single',
                    'choices' => [
                        ['name' => '', 'price' => '', 'quantity' => ''],
                    ],
                ],
            ]),
        ];

        $response = $this->actingAs($owner)
            ->post(route('ftadmin.menu.store'), $payload, ['Accept' => 'application/json']);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('Please provide pricing', (string) $response->json('message'));
        $this->assertDatabaseCount('menus', 0);
    }

    public function test_allows_base_price_zero_even_without_named_choices(): void
    {
        $owner = $this->createUser(['role' => User::ROLE_FOOD_TRUCK_ADMIN]);
        $truck = $this->createTruck($owner);

        $payload = [
            'name' => 'Free Water',
            'category' => 'drinks',
            'base_price' => 0,
            'quantity' => 100,
            'description' => 'test',
            'option_groups' => json_encode([]),
        ];

        $response = $this->actingAs($owner)
            ->post(route('ftadmin.menu.store'), $payload, ['Accept' => 'application/json']);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('menus', [
            'foodtruck_id' => $truck->id,
            'name' => 'Free Water',
            'base_price' => 0,
        ]);
    }
}
