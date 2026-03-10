<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Menu;
use App\Models\Order;

class DummyPendingOrdersSeeder extends Seeder
{
    public function run()
    {
        // Create a dummy customer user
        $user = User::factory()->create(['role' => 0]);

        // Get random menus for foodtruck_id = 4
        $menus = Menu::where('foodtruck_id', 4)->inRandomOrder()->take(3)->get();

        // Build items array and total for the order
        $items = [];
        $total = 0;
        foreach ($menus as $menu) {
            $quantity = rand(1, 3);
            $items[] = [
                'menu_id' => $menu->id,
                'name' => $menu->name,
                'quantity' => $quantity,
                'price' => $menu->base_price,
                'subtotal' => $menu->base_price * $quantity,
            ];
            $total += $menu->base_price * $quantity;
        }

        Order::create([
            'foodtruck_id' => 4,
            'customer_name' => $user->full_name ?? 'Customer',
            'items' => $items,
            'total' => $total,
            'status' => 'pending',
            'accepted_by' => null,
            'notes' => 'Dummy test order',
        ]);
    }
}
