<?php

namespace Database\Seeders;

use App\Models\FoodTruck;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\MenuChoice;
use App\Models\MenuOptionGroup;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkerPunchCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');
        $now = now();

        // ─── System Admin ───
        User::updateOrCreate(
            ['email' => 'AdminSystem1@ftos.com'],
            [
                'full_name' => 'System Admin',
                'password' => Hash::make('truckadmin111_'),
                'role' => User::ROLE_SYSTEM_ADMIN,
                'phone_no' => '000000000',
                'email_verified_at' => $now,
            ]
        );

        // ─── FT Admins (role=2) ───
        $ahmad = User::updateOrCreate(
            ['email' => 'ahmad@ftos.com'],
            [
                'full_name' => 'Ahmad bin Ismail',
                'password' => $password,
                'role' => User::ROLE_FOOD_TRUCK_ADMIN,
                'phone_no' => '60121234567',
                'email_verified_at' => $now,
            ]
        );

        $siti = User::updateOrCreate(
            ['email' => 'siti@ftos.com'],
            [
                'full_name' => 'Siti Nurhaliza binti Razak',
                'password' => $password,
                'role' => User::ROLE_FOOD_TRUCK_ADMIN,
                'phone_no' => '60139876543',
                'email_verified_at' => $now,
            ]
        );

        $raj = User::updateOrCreate(
            ['email' => 'raj@ftos.com'],
            [
                'full_name' => 'Raj Kumar a/l Muthu',
                'password' => $password,
                'role' => User::ROLE_FOOD_TRUCK_ADMIN,
                'phone_no' => '60147654321',
                'email_verified_at' => $now,
            ]
        );

        // ─── Food Trucks ───
        $truckAhmad = FoodTruck::updateOrCreate(
            ['business_license_no' => 'BL-KL-2024-0147'],
            [
                'foodtruck_name' => 'Warung Abang Ahmad',
                'foodtruck_desc' => 'Authentic Malaysian street food — nasi lemak, mee goreng, and all your kampung favourites.',
                'user_id' => $ahmad->id,
                'status' => 'approved',
                'is_operational' => true,
            ]
        );

        $truckSiti = FoodTruck::updateOrCreate(
            ['business_license_no' => 'BL-SL-2024-0293'],
            [
                'foodtruck_name' => "Siti's Satay & Grill",
                'foodtruck_desc' => 'Best satay in town! Freshly grilled meats with homemade kuah kacang.',
                'user_id' => $siti->id,
                'status' => 'approved',
                'is_operational' => true,
            ]
        );

        $truckRaj = FoodTruck::updateOrCreate(
            ['business_license_no' => 'BL-PJ-2024-0418'],
            [
                'foodtruck_name' => "Raj's Roti Corner",
                'foodtruck_desc' => 'Roti canai, thosai, and mamak favourites made fresh to order.',
                'user_id' => $raj->id,
                'status' => 'approved',
                'is_operational' => true,
            ]
        );

        // ─── FT Workers (role=3) ───
        $workers = [
            ['Hafiz bin Abu Bakar', 'hafiz@ftos.com', '60111112222', $truckAhmad->id],
            ['Aisyah binti Yusof', 'aisyah@ftos.com', '60111113333', $truckAhmad->id],
            ['Farid bin Osman', 'farid@ftos.com', '60122224444', $truckSiti->id],
            ['Nurul Huda binti Hassan', 'nurul@ftos.com', '60122225555', $truckSiti->id],
            ['Dinesh a/l Rajan', 'dinesh@ftos.com', '60133336666', $truckRaj->id],
            ['Priya a/p Suresh', 'priya@ftos.com', '60133337777', $truckRaj->id],
        ];

        $workerModels = [];
        foreach ($workers as [$name, $email, $phone, $truckId]) {
            $workerModels[] = User::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $name,
                    'password' => $password,
                    'role' => User::ROLE_FOOD_TRUCK_WORKER,
                    'phone_no' => $phone,
                    'foodtruck_id' => $truckId,
                    'email_verified_at' => $now,
                ]
            );
        }

        // ─── Customers (role=1) ───
        $customers = [
            ['Tan Wei Ming', 'weiming@ftos.com', '60141112222'],
            ['Lim Mei Ling', 'meiling@ftos.com', '60152223333'],
            ['Muhammad Aiman bin Zakaria', 'aiman@ftos.com', '60163334444'],
            ['Kavitha a/p Krishnan', 'kavitha@ftos.com', '60174445555'],
            ['Zulkifli bin Hamzah', 'zulkifli@ftos.com', '60185556666'],
        ];

        $customerModels = [];
        foreach ($customers as [$name, $email, $phone]) {
            $customerModels[] = User::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $name,
                    'password' => $password,
                    'role' => User::ROLE_CUSTOMER,
                    'phone_no' => $phone,
                    'email_verified_at' => $now,
                ]
            );
        }

        // ─── Menu Categories ───
        $colors = ['red', 'blue', 'green', 'purple', 'orange'];
        foreach ([$truckAhmad, $truckSiti, $truckRaj] as $truck) {
            foreach (['Foods', 'Drinks', 'Desserts'] as $i => $cat) {
                MenuCategory::updateOrCreate(
                    ['foodtruck_id' => $truck->id, 'name' => $cat],
                    ['color' => $colors[$i], 'sort_order' => $i]
                );
            }
        }
        // Custom category for Raj
        MenuCategory::updateOrCreate(
            ['foodtruck_id' => $truckRaj->id, 'name' => 'Mamak Specials'],
            ['color' => 'orange', 'sort_order' => 3]
        );

        // ─── Menu Items ───
        // Helper to create menu items
        $createMenu = function ($truckId, $items) {
            $created = [];
            foreach ($items as $item) {
                $created[$item['name']] = Menu::updateOrCreate(
                    ['foodtruck_id' => $truckId, 'name' => $item['name']],
                    [
                        'category' => $item['category'],
                        'base_price' => $item['price'],
                        'quantity' => $item['qty'],
                        'description' => $item['desc'] ?? null,
                        'status' => $item['status'] ?? 'available',
                    ]
                );
            }
            return $created;
        };

        $ahmadMenus = $createMenu($truckAhmad->id, [
            ['name' => 'Nasi Lemak Special', 'category' => 'Foods', 'price' => 8.50, 'qty' => 50, 'desc' => 'Nasi lemak with sambal, ikan bilis, kacang, timun, and telur rebus'],
            ['name' => 'Mee Goreng Mamak', 'category' => 'Foods', 'price' => 7.00, 'qty' => 40, 'desc' => 'Spicy fried noodles mamak style'],
            ['name' => 'Nasi Goreng Kampung', 'category' => 'Foods', 'price' => 8.00, 'qty' => 35, 'desc' => 'Village-style fried rice with anchovies'],
            ['name' => 'Roti Canai Biasa', 'category' => 'Foods', 'price' => 2.50, 'qty' => 80, 'desc' => 'Plain roti canai with dhal curry'],
            ['name' => 'Maggi Goreng', 'category' => 'Foods', 'price' => 6.50, 'qty' => 30, 'desc' => 'Fried Maggi noodles with egg and vegetables'],
            ['name' => 'Teh Tarik', 'category' => 'Drinks', 'price' => 2.50, 'qty' => 100, 'desc' => 'Pulled milk tea'],
            ['name' => 'Kopi O', 'category' => 'Drinks', 'price' => 2.00, 'qty' => 100, 'desc' => 'Black coffee'],
            ['name' => 'Milo Ais', 'category' => 'Drinks', 'price' => 3.50, 'qty' => 60, 'desc' => 'Iced Milo chocolate drink'],
            ['name' => 'Air Sirap Limau', 'category' => 'Drinks', 'price' => 2.50, 'qty' => 50, 'desc' => 'Rose syrup with lime', 'status' => 'unavailable'],
            ['name' => 'Cendol', 'category' => 'Desserts', 'price' => 4.00, 'qty' => 25, 'desc' => 'Shaved ice with pandan jelly, coconut milk, and gula melaka'],
            ['name' => 'Kuih Lapis', 'category' => 'Desserts', 'price' => 2.00, 'qty' => 20, 'desc' => 'Layered steamed kuih'],
        ]);

        $sitiMenus = $createMenu($truckSiti->id, [
            ['name' => 'Satay Ayam (10pcs)', 'category' => 'Foods', 'price' => 12.00, 'qty' => 40, 'desc' => 'Chicken satay with kuah kacang, nasi impit, and cucumber'],
            ['name' => 'Satay Daging (10pcs)', 'category' => 'Foods', 'price' => 15.00, 'qty' => 30, 'desc' => 'Beef satay with kuah kacang'],
            ['name' => 'Ayam Percik', 'category' => 'Foods', 'price' => 10.00, 'qty' => 25, 'desc' => 'Grilled chicken with spicy coconut sauce'],
            ['name' => 'Nasi Impit + Kuah Kacang', 'category' => 'Foods', 'price' => 5.00, 'qty' => 30, 'desc' => 'Compressed rice with peanut sauce'],
            ['name' => 'Burger Daging Special', 'category' => 'Foods', 'price' => 9.00, 'qty' => 20, 'desc' => 'Beef burger with special sauce', 'status' => 'unavailable'],
            ['name' => 'Air Kelapa', 'category' => 'Drinks', 'price' => 4.00, 'qty' => 40, 'desc' => 'Fresh coconut water'],
            ['name' => 'Bandung', 'category' => 'Drinks', 'price' => 3.00, 'qty' => 50, 'desc' => 'Rose milk drink'],
            ['name' => 'Teh O Ais Limau', 'category' => 'Drinks', 'price' => 3.00, 'qty' => 60, 'desc' => 'Iced lime tea'],
            ['name' => 'Pisang Goreng Cheese', 'category' => 'Desserts', 'price' => 5.00, 'qty' => 25, 'desc' => 'Fried banana with melted cheese'],
            ['name' => 'Keropok Lekor', 'category' => 'Desserts', 'price' => 4.00, 'qty' => 30, 'desc' => 'Fish crackers from Terengganu'],
        ]);

        $rajMenus = $createMenu($truckRaj->id, [
            ['name' => 'Roti Canai Kosong', 'category' => 'Foods', 'price' => 1.50, 'qty' => 100, 'desc' => 'Plain roti canai with dhal'],
            ['name' => 'Roti Telur', 'category' => 'Foods', 'price' => 2.50, 'qty' => 80, 'desc' => 'Roti canai with egg'],
            ['name' => 'Roti Bom', 'category' => 'Foods', 'price' => 3.50, 'qty' => 40, 'desc' => 'Sweet thick roti with condensed milk'],
            ['name' => 'Thosai', 'category' => 'Foods', 'price' => 2.00, 'qty' => 60, 'desc' => 'Crispy Indian crepe with chutney'],
            ['name' => 'Mee Goreng Mamak', 'category' => 'Foods', 'price' => 7.00, 'qty' => 35, 'desc' => 'Mamak fried noodles'],
            ['name' => 'Maggi Goreng Sup', 'category' => 'Foods', 'price' => 7.50, 'qty' => 30, 'desc' => 'Fried Maggi with soup on the side'],
            ['name' => 'Nasi Kandar Campur', 'category' => 'Mamak Specials', 'price' => 10.00, 'qty' => 25, 'desc' => 'Rice with mixed curries and sides'],
            ['name' => 'Teh Tarik', 'category' => 'Drinks', 'price' => 2.00, 'qty' => 100, 'desc' => 'Classic pulled milk tea'],
            ['name' => 'Milo Dinosaur', 'category' => 'Drinks', 'price' => 4.50, 'qty' => 50, 'desc' => 'Iced Milo with extra Milo powder on top'],
            ['name' => 'Lassi Mango', 'category' => 'Drinks', 'price' => 5.00, 'qty' => 30, 'desc' => 'Mango yogurt smoothie'],
            ['name' => 'Roti Tisu', 'category' => 'Desserts', 'price' => 4.00, 'qty' => 20, 'desc' => 'Paper-thin crispy roti with condensed milk'],
        ]);

        // ─── Menu Option Groups & Choices ───
        // Nasi Lemak: Protein
        $grp = MenuOptionGroup::updateOrCreate(
            ['menu_id' => $ahmadMenus['Nasi Lemak Special']->id, 'name' => 'Protein'],
            ['selection_type' => 'single', 'sort_order' => 0]
        );
        foreach ([['Ayam Goreng', 2.00], ['Rendang', 3.00], ['Telur Mata', 1.00]] as $i => [$n, $p]) {
            MenuChoice::updateOrCreate(
                ['group_id' => $grp->id, 'name' => $n],
                ['price' => $p, 'sort_order' => $i, 'status' => 'available']
            );
        }

        // Nasi Lemak: Spice Level
        $grp = MenuOptionGroup::updateOrCreate(
            ['menu_id' => $ahmadMenus['Nasi Lemak Special']->id, 'name' => 'Spice Level'],
            ['selection_type' => 'single', 'sort_order' => 1]
        );
        foreach ([['Mild', 0], ['Medium', 0], ['Extra Pedas', 0]] as $i => [$n, $p]) {
            MenuChoice::updateOrCreate(
                ['group_id' => $grp->id, 'name' => $n],
                ['price' => $p, 'sort_order' => $i, 'status' => 'available']
            );
        }

        // Teh Tarik (Ahmad): Temperature
        $grp = MenuOptionGroup::updateOrCreate(
            ['menu_id' => $ahmadMenus['Teh Tarik']->id, 'name' => 'Temperature'],
            ['selection_type' => 'single', 'sort_order' => 0]
        );
        foreach ([['Hot', 0], ['Iced', 0.50]] as $i => [$n, $p]) {
            MenuChoice::updateOrCreate(
                ['group_id' => $grp->id, 'name' => $n],
                ['price' => $p, 'sort_order' => $i, 'status' => 'available']
            );
        }

        // Satay Ayam: Quantity
        $grp = MenuOptionGroup::updateOrCreate(
            ['menu_id' => $sitiMenus['Satay Ayam (10pcs)']->id, 'name' => 'Quantity'],
            ['selection_type' => 'single', 'sort_order' => 0]
        );
        foreach ([['10 pcs', 0], ['20 pcs', 12.00]] as $i => [$n, $p]) {
            MenuChoice::updateOrCreate(
                ['group_id' => $grp->id, 'name' => $n],
                ['price' => $p, 'sort_order' => $i, 'status' => 'available']
            );
        }

        // Roti Canai Kosong (Raj): Add-on
        $grp = MenuOptionGroup::updateOrCreate(
            ['menu_id' => $rajMenus['Roti Canai Kosong']->id, 'name' => 'Add-on'],
            ['selection_type' => 'multiple', 'sort_order' => 0]
        );
        foreach ([['Egg', 1.00], ['Cheese', 1.50], ['Sardine', 2.00]] as $i => [$n, $p]) {
            MenuChoice::updateOrCreate(
                ['group_id' => $grp->id, 'name' => $n],
                ['price' => $p, 'sort_order' => $i, 'status' => 'available']
            );
        }

        // ─── Orders ───
        // Clear existing orders to avoid duplicates on re-seed
        Order::query()->delete();

        $paymentMethods = ['tng', 'grabpay', 'fpx_maybank', 'cash'];

        $orders = [
            // Warung Abang Ahmad — done orders (older)
            [
                'foodtruck_id' => $truckAhmad->id,
                'customer_id' => $customerModels[0]->id,
                'customer_name' => 'Tan Wei Ming',
                'items' => [
                    ['name' => 'Nasi Lemak Special', 'quantity' => 2, 'price' => 8.50, 'options' => ['Protein: Ayam Goreng (+RM2.00)', 'Spice Level: Medium']],
                    ['name' => 'Teh Tarik', 'quantity' => 2, 'price' => 2.50, 'options' => ['Temperature: Hot']],
                ],
                'total' => 26.00,
                'status' => 'done',
                'order_type' => 'self_pickup',
                'payment_method' => 'tng',
                'accepted_by' => $workerModels[0]->id,
                'created_at' => $now->copy()->subDays(12),
            ],
            [
                'foodtruck_id' => $truckAhmad->id,
                'customer_id' => $customerModels[2]->id,
                'customer_name' => 'Muhammad Aiman bin Zakaria',
                'items' => [
                    ['name' => 'Mee Goreng Mamak', 'quantity' => 1, 'price' => 7.00, 'options' => []],
                    ['name' => 'Milo Ais', 'quantity' => 1, 'price' => 3.50, 'options' => []],
                ],
                'total' => 10.50,
                'status' => 'done',
                'order_type' => 'table',
                'table_number' => 3,
                'payment_method' => 'cash',
                'accepted_by' => $workerModels[1]->id,
                'created_at' => $now->copy()->subDays(10),
            ],
            // Siti's Satay — done orders
            [
                'foodtruck_id' => $truckSiti->id,
                'customer_id' => $customerModels[1]->id,
                'customer_name' => 'Lim Mei Ling',
                'items' => [
                    ['name' => 'Satay Ayam (10pcs)', 'quantity' => 2, 'price' => 12.00, 'options' => ['Quantity: 10 pcs']],
                    ['name' => 'Nasi Impit + Kuah Kacang', 'quantity' => 2, 'price' => 5.00, 'options' => []],
                    ['name' => 'Bandung', 'quantity' => 2, 'price' => 3.00, 'options' => []],
                ],
                'total' => 40.00,
                'status' => 'done',
                'order_type' => 'self_pickup',
                'payment_method' => 'grabpay',
                'accepted_by' => $workerModels[2]->id,
                'created_at' => $now->copy()->subDays(8),
            ],
            [
                'foodtruck_id' => $truckSiti->id,
                'customer_id' => $customerModels[3]->id,
                'customer_name' => 'Kavitha a/p Krishnan',
                'items' => [
                    ['name' => 'Ayam Percik', 'quantity' => 1, 'price' => 10.00, 'options' => []],
                    ['name' => 'Air Kelapa', 'quantity' => 1, 'price' => 4.00, 'options' => []],
                ],
                'total' => 14.00,
                'status' => 'done',
                'order_type' => 'self_pickup',
                'payment_method' => 'tng',
                'accepted_by' => $workerModels[3]->id,
                'created_at' => $now->copy()->subDays(6),
            ],
            // Raj's Roti — done orders
            [
                'foodtruck_id' => $truckRaj->id,
                'customer_id' => $customerModels[4]->id,
                'customer_name' => 'Zulkifli bin Hamzah',
                'items' => [
                    ['name' => 'Roti Canai Kosong', 'quantity' => 3, 'price' => 1.50, 'options' => []],
                    ['name' => 'Roti Telur', 'quantity' => 2, 'price' => 2.50, 'options' => []],
                    ['name' => 'Teh Tarik', 'quantity' => 2, 'price' => 2.00, 'options' => []],
                ],
                'total' => 13.50,
                'status' => 'done',
                'order_type' => 'table',
                'table_number' => 5,
                'payment_method' => 'fpx_maybank',
                'accepted_by' => $workerModels[4]->id,
                'created_at' => $now->copy()->subDays(5),
            ],
            [
                'foodtruck_id' => $truckRaj->id,
                'customer_id' => $customerModels[0]->id,
                'customer_name' => 'Tan Wei Ming',
                'items' => [
                    ['name' => 'Nasi Kandar Campur', 'quantity' => 1, 'price' => 10.00, 'options' => []],
                    ['name' => 'Milo Dinosaur', 'quantity' => 1, 'price' => 4.50, 'options' => []],
                ],
                'total' => 14.50,
                'status' => 'done',
                'order_type' => 'self_pickup',
                'payment_method' => 'cash',
                'accepted_by' => $workerModels[5]->id,
                'created_at' => $now->copy()->subDays(3),
            ],
            // Pending orders (recent)
            [
                'foodtruck_id' => $truckAhmad->id,
                'customer_id' => $customerModels[1]->id,
                'customer_name' => 'Lim Mei Ling',
                'items' => [
                    ['name' => 'Nasi Goreng Kampung', 'quantity' => 1, 'price' => 8.00, 'options' => []],
                    ['name' => 'Kopi O', 'quantity' => 1, 'price' => 2.00, 'options' => []],
                ],
                'total' => 10.00,
                'status' => 'pending',
                'order_type' => 'self_pickup',
                'payment_method' => 'tng',
                'created_at' => $now->copy()->subMinutes(15),
            ],
            [
                'foodtruck_id' => $truckSiti->id,
                'customer_id' => $customerModels[4]->id,
                'customer_name' => 'Zulkifli bin Hamzah',
                'items' => [
                    ['name' => 'Satay Daging (10pcs)', 'quantity' => 1, 'price' => 15.00, 'options' => []],
                    ['name' => 'Satay Ayam (10pcs)', 'quantity' => 1, 'price' => 12.00, 'options' => ['Quantity: 10 pcs']],
                    ['name' => 'Teh O Ais Limau', 'quantity' => 2, 'price' => 3.00, 'options' => []],
                ],
                'total' => 33.00,
                'status' => 'pending',
                'order_type' => 'table',
                'table_number' => 2,
                'payment_method' => 'grabpay',
                'created_at' => $now->copy()->subMinutes(8),
            ],
            [
                'foodtruck_id' => $truckRaj->id,
                'customer_id' => $customerModels[2]->id,
                'customer_name' => 'Muhammad Aiman bin Zakaria',
                'items' => [
                    ['name' => 'Roti Bom', 'quantity' => 2, 'price' => 3.50, 'options' => []],
                    ['name' => 'Lassi Mango', 'quantity' => 1, 'price' => 5.00, 'options' => []],
                ],
                'total' => 12.00,
                'status' => 'pending',
                'order_type' => 'self_pickup',
                'payment_method' => 'fpx_maybank',
                'created_at' => $now->copy()->subMinutes(3),
            ],
            // Accepted / Preparing / Prepared
            [
                'foodtruck_id' => $truckAhmad->id,
                'customer_id' => $customerModels[3]->id,
                'customer_name' => 'Kavitha a/p Krishnan',
                'items' => [
                    ['name' => 'Nasi Lemak Special', 'quantity' => 1, 'price' => 8.50, 'options' => ['Protein: Rendang (+RM3.00)', 'Spice Level: Extra Pedas']],
                    ['name' => 'Cendol', 'quantity' => 1, 'price' => 4.00, 'options' => []],
                ],
                'total' => 15.50,
                'status' => 'accepted',
                'order_type' => 'self_pickup',
                'payment_method' => 'tng',
                'accepted_by' => $workerModels[0]->id,
                'created_at' => $now->copy()->subMinutes(25),
            ],
            [
                'foodtruck_id' => $truckSiti->id,
                'customer_id' => $customerModels[0]->id,
                'customer_name' => 'Tan Wei Ming',
                'items' => [
                    ['name' => 'Satay Ayam (10pcs)', 'quantity' => 1, 'price' => 12.00, 'options' => ['Quantity: 20 pcs']],
                    ['name' => 'Pisang Goreng Cheese', 'quantity' => 1, 'price' => 5.00, 'options' => []],
                ],
                'total' => 29.00,
                'status' => 'preparing',
                'order_type' => 'self_pickup',
                'payment_method' => 'grabpay',
                'accepted_by' => $workerModels[2]->id,
                'created_at' => $now->copy()->subMinutes(20),
            ],
            [
                'foodtruck_id' => $truckRaj->id,
                'customer_id' => $customerModels[1]->id,
                'customer_name' => 'Lim Mei Ling',
                'items' => [
                    ['name' => 'Thosai', 'quantity' => 2, 'price' => 2.00, 'options' => []],
                    ['name' => 'Maggi Goreng Sup', 'quantity' => 1, 'price' => 7.50, 'options' => []],
                    ['name' => 'Teh Tarik', 'quantity' => 1, 'price' => 2.00, 'options' => []],
                ],
                'total' => 13.50,
                'status' => 'prepared',
                'order_type' => 'table',
                'table_number' => 1,
                'payment_method' => 'cash',
                'accepted_by' => $workerModels[4]->id,
                'created_at' => $now->copy()->subMinutes(35),
            ],
            [
                'foodtruck_id' => $truckAhmad->id,
                'customer_id' => $customerModels[4]->id,
                'customer_name' => 'Zulkifli bin Hamzah',
                'items' => [
                    ['name' => 'Maggi Goreng', 'quantity' => 1, 'price' => 6.50, 'options' => []],
                    ['name' => 'Teh Tarik', 'quantity' => 1, 'price' => 2.50, 'options' => ['Temperature: Iced']],
                ],
                'total' => 9.50,
                'status' => 'preparing',
                'order_type' => 'table',
                'table_number' => 7,
                'payment_method' => 'cash',
                'accepted_by' => $workerModels[1]->id,
                'created_at' => $now->copy()->subMinutes(18),
            ],
            // Rejected orders
            [
                'foodtruck_id' => $truckSiti->id,
                'customer_id' => $customerModels[2]->id,
                'customer_name' => 'Muhammad Aiman bin Zakaria',
                'items' => [
                    ['name' => 'Burger Daging Special', 'quantity' => 2, 'price' => 9.00, 'options' => []],
                ],
                'total' => 18.00,
                'status' => 'rejected',
                'order_type' => 'self_pickup',
                'payment_method' => 'tng',
                'notes' => 'Sorry, burger patties habis for today',
                'created_at' => $now->copy()->subDays(2),
            ],
            [
                'foodtruck_id' => $truckRaj->id,
                'customer_id' => $customerModels[3]->id,
                'customer_name' => 'Kavitha a/p Krishnan',
                'items' => [
                    ['name' => 'Nasi Kandar Campur', 'quantity' => 3, 'price' => 10.00, 'options' => []],
                ],
                'total' => 30.00,
                'status' => 'rejected',
                'order_type' => 'table',
                'table_number' => 4,
                'payment_method' => 'fpx_maybank',
                'notes' => 'Kitchen closing early today, sorry!',
                'created_at' => $now->copy()->subDays(1),
            ],
        ];

        foreach ($orders as $orderData) {
            Order::create($orderData);
        }

        // ─── Worker Punch Cards ───
        WorkerPunchCard::query()->delete();

        $punchData = [
            // Warung Abang Ahmad workers
            ['user_id' => $workerModels[0]->id, 'foodtruck_id' => $truckAhmad->id, 'punched_in_at' => $now->copy()->subDays(2)->setTime(7, 0), 'punched_out_at' => $now->copy()->subDays(2)->setTime(15, 0)],
            ['user_id' => $workerModels[1]->id, 'foodtruck_id' => $truckAhmad->id, 'punched_in_at' => $now->copy()->subDays(2)->setTime(17, 0), 'punched_out_at' => $now->copy()->subDays(2)->setTime(23, 0)],
            ['user_id' => $workerModels[0]->id, 'foodtruck_id' => $truckAhmad->id, 'punched_in_at' => $now->copy()->subDays(1)->setTime(7, 30), 'punched_out_at' => $now->copy()->subDays(1)->setTime(14, 30)],
            ['user_id' => $workerModels[1]->id, 'foodtruck_id' => $truckAhmad->id, 'punched_in_at' => $now->copy()->subDays(1)->setTime(17, 30), 'punched_out_at' => $now->copy()->subDays(1)->setTime(22, 30)],
            ['user_id' => $workerModels[0]->id, 'foodtruck_id' => $truckAhmad->id, 'punched_in_at' => $now->copy()->setTime(7, 0), 'punched_out_at' => null], // Currently working

            // Siti's workers
            ['user_id' => $workerModels[2]->id, 'foodtruck_id' => $truckSiti->id, 'punched_in_at' => $now->copy()->subDays(3)->setTime(16, 0), 'punched_out_at' => $now->copy()->subDays(3)->setTime(22, 0)],
            ['user_id' => $workerModels[3]->id, 'foodtruck_id' => $truckSiti->id, 'punched_in_at' => $now->copy()->subDays(2)->setTime(16, 30), 'punched_out_at' => $now->copy()->subDays(2)->setTime(22, 30)],
            ['user_id' => $workerModels[2]->id, 'foodtruck_id' => $truckSiti->id, 'punched_in_at' => $now->copy()->subDays(1)->setTime(17, 0), 'punched_out_at' => $now->copy()->subDays(1)->setTime(23, 0)],
            ['user_id' => $workerModels[3]->id, 'foodtruck_id' => $truckSiti->id, 'punched_in_at' => $now->copy()->setTime(16, 0), 'punched_out_at' => null], // Currently working

            // Raj's workers
            ['user_id' => $workerModels[4]->id, 'foodtruck_id' => $truckRaj->id, 'punched_in_at' => $now->copy()->subDays(2)->setTime(6, 30), 'punched_out_at' => $now->copy()->subDays(2)->setTime(14, 0)],
            ['user_id' => $workerModels[5]->id, 'foodtruck_id' => $truckRaj->id, 'punched_in_at' => $now->copy()->subDays(2)->setTime(14, 0), 'punched_out_at' => $now->copy()->subDays(2)->setTime(22, 0)],
            ['user_id' => $workerModels[4]->id, 'foodtruck_id' => $truckRaj->id, 'punched_in_at' => $now->copy()->subDays(1)->setTime(7, 0), 'punched_out_at' => $now->copy()->subDays(1)->setTime(15, 0)],
            ['user_id' => $workerModels[5]->id, 'foodtruck_id' => $truckRaj->id, 'punched_in_at' => $now->copy()->setTime(6, 0), 'punched_out_at' => null], // Currently working
        ];

        foreach ($punchData as $punch) {
            WorkerPunchCard::create($punch);
        }
    }
}
