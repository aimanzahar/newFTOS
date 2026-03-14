<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TruckApprovalController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WorkerPunchCardController;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkerPunchCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

Route::get('/', [PublicController::class, 'landing'])->name('public.landing');
Route::get('/trucks/{id}', [PublicController::class, 'truckMenu'])->name('public.truck-menu');

// Unified post-auth entrypoint: always redirect by role to real dashboard.
Route::get('/dashboard', function () {
    $user = Auth::user();

    return match ((int) ($user->role ?? 0)) {
        User::ROLE_CUSTOMER => redirect()->route('customer.dashboard'),
        User::ROLE_FOOD_TRUCK_ADMIN => redirect()->route('ftadmin.dashboard'),
        User::ROLE_FOOD_TRUCK_WORKER => redirect()->route('ftworker.dashboard'),
        User::ROLE_SYSTEM_ADMIN => redirect()->route('admin.dashboard'),
        default => redirect('/'),
    };
})->middleware(['auth'])->name('dashboard');

/**
 * Customer Routes
 */
Route::middleware(['auth', 'role:1'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', function () {
        return view('customer.customer-dashboard');
    })->name('dashboard');

    Route::get('/order-status-snapshot', function () {
        $user = Auth::user();

        $orders = Order::query()
            ->where('customer_id', $user->id)
            ->latest()
            ->get(['id', 'status', 'updated_at', 'notes']);

        $snapshotToken = sha1(
            $orders
                ->map(function ($order) {
                    return implode('|', [
                        (string) $order->id,
                        (string) ($order->status ?? ''),
                        (string) optional($order->updated_at)->toIso8601String(),
                        (string) ($order->notes ?? ''),
                    ]);
                })
                ->implode(';')
        );

        return response()->json([
            'success' => true,
            'snapshot_token' => $snapshotToken,
        ]);
    })->name('order-status-snapshot');

    Route::get('/browse', [CustomerController::class, 'browse'])->name('browse');
    Route::get('/truck/{id}', [CustomerController::class, 'truckMenu'])->name('truck-menu');
    Route::post('/orders', [CustomerController::class, 'placeOrder'])->name('place-order');
});

/**
 * Shared Profile Routes
 * These are used by Customers, Admins, FT Admins, and FT Workers.
 */
Route::middleware(['auth', 'ftadmin.status', 'ftworker.status'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * Admin Routes (Super Admin)
 */
Route::middleware(['auth', 'role:6'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/pending', [AdminController::class, 'pendingTrucks'])->name('pending.trucks');
    Route::get('/approved', [AdminController::class, 'approvedTrucks'])->name('approved.trucks');
    Route::get('/global-menus', [AdminController::class, 'globalMenus'])->name('global-menus');
    Route::post('/approve-user/{id}', [AdminController::class, 'approveUser'])->name('approve.user');
    Route::post('/trucks/{id}/approve', [TruckApprovalController::class, 'approve'])->name('approve-truck');
    Route::delete('/trucks/{id}/reject', [TruckApprovalController::class, 'reject'])->name('reject-truck');
    Route::get('/trucks/{truckId}/orders', [AdminController::class, 'truckOrders'])->name('truck.orders');
    Route::patch('/orders/{orderId}/status', [AdminController::class, 'updateOrderStatus'])->name('order.update-status');
    Route::patch('/trucks/{truckId}/update-details', [AdminController::class, 'updateTruckDetails'])->name('truck.update-details');
    Route::patch('/trucks/{truckId}/users/{userId}/status', [AdminController::class, 'updateTruckUserStatus'])->name('truck-user.update-status');

    Route::post('/trucks/{truckId}/toggle-operational', function ($truckId) {
        $truck = \App\Models\FoodTruck::find($truckId);
        if (!$truck) return response()->json(['success' => false, 'message' => 'Truck not found'], 404);
        if (($truck->status ?? null) !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Only approved trucks can be toggled.'], 403);
        }

        $updatedOperational = null;
        $autoRejectedCount = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($truck, &$updatedOperational, &$autoRejectedCount) {
            $truck->is_operational = !$truck->is_operational;
            $truck->save();
            $updatedOperational = (bool) $truck->is_operational;

            if ($updatedOperational) return;

            $offlineRefundNote = 'Truck was taken offline by System Admin. Your paid order was rejected and refund processing has started.';
            $rejectableStatuses = ['pending', 'accepted', 'preparing', 'prepared', 'ready_for_pickup', 'delivery'];

            $autoRejectedCount = \App\Models\Order::query()
                ->where('foodtruck_id', $truck->id)
                ->whereIn('status', $rejectableStatuses)
                ->update([
                    'status' => 'rejected',
                    'accepted_by' => null,
                    'notes' => $offlineRefundNote,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'success' => true,
            'is_operational' => (bool) $updatedOperational,
            'auto_rejected_orders' => (int) $autoRejectedCount,
        ]);
    })->name('truck.toggle-operational');
});

/**
 * Food Truck Admin Routes (ftadmin)
 */
Route::middleware(['auth', 'role:2', 'ftadmin.status'])->prefix('ftadmin')->name('ftadmin.')->group(function () {
    $buildRevenueSummary = function ($foodtruckId) {
        $completedRevenueOrders = Order::query()
            ->with('worker:id,full_name')
            ->where('foodtruck_id', $foodtruckId)
            ->where('status', 'done')
            ->orderByDesc('updated_at')
            ->get(['id', 'accepted_by', 'customer_name', 'items', 'total', 'created_at', 'updated_at']);

        $totalRevenueAmount = (float) $completedRevenueOrders->sum('total');
        $completedOrdersCount = $completedRevenueOrders->count();

        $completedRevenueRows = $completedRevenueOrders
            ->flatMap(function ($order) {
                $items = is_array($order->items) ? $order->items : [];

                return collect($items)->map(function ($item) use ($order) {
                    $quantity = max(0, (int) ($item['quantity'] ?? 0));

                    $itemTotal = is_numeric($item['item_total'] ?? null)
                        ? (float) $item['item_total']
                        : 0.0;

                    if ($itemTotal <= 0 && $quantity > 0) {
                        $basePrice = is_numeric($item['base_price'] ?? null)
                            ? (float) $item['base_price']
                            : 0.0;

                        $choicesTotal = collect($item['selected_choices'] ?? [])->sum(function ($choice) {
                            return is_numeric($choice['price'] ?? null)
                                ? (float) $choice['price']
                                : 0.0;
                        });

                        $itemTotal = ($basePrice + $choicesTotal) * $quantity;
                    }

                    $unitPrice = $quantity > 0 ? ($itemTotal / $quantity) : 0.0;

                    return [
                        'order_id' => $order->id,
                        'customer_name' => $order->customer_name ?: 'Customer',
                        'completed_by' => $order->worker?->full_name ?: '-',
                        'menu_name' => $item['name'] ?? 'Menu Item',
                        'menu_quantity' => $quantity,
                        'menu_price' => round($unitPrice, 2),
                        'menu_total_price' => round($itemTotal, 2),
                        'purchased_at' => optional($order->created_at)->toIso8601String(),
                    ];
                });
            })
            ->values();

        return [
            'completedRevenueRows' => $completedRevenueRows,
            'totalRevenueAmount' => round($totalRevenueAmount, 2),
            'completedOrdersCount' => $completedOrdersCount,
        ];
    };

    $buildStaffDirectorySummary = function ($foodtruckId) {
        $rawFtworkers = User::query()
            ->where('role', User::ROLE_FOOD_TRUCK_WORKER)
            ->where('foodtruck_id', $foodtruckId)
            ->orderBy('full_name', 'asc')
            ->get();

        $workerIds = $rawFtworkers->pluck('id');
        $activePunchCardsByWorkerId = WorkerPunchCard::query()
            ->where('foodtruck_id', $foodtruckId)
            ->whereIn('user_id', $workerIds)
            ->whereNull('punched_out_at')
            ->orderByDesc('punched_in_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($cards) => $cards->first());

        $workers = $rawFtworkers->map(function ($worker) use ($activePunchCardsByWorkerId) {
            $activeCard = $activePunchCardsByWorkerId->get($worker->id);

            return [
                'id' => $worker->id,
                'full_name' => $worker->full_name,
                'email' => $worker->email,
                'phone_no' => $worker->phone_no,
                'status' => $worker->status,
                'status_locked_by_system_admin' => (bool) ($worker->status_locked_by_system_admin ?? false),
                'shift_status' => $activeCard ? 'active' : 'inactive',
                'active_punched_in_at' => $activeCard?->punched_in_at?->toIso8601String(),
            ];
        })->values();

        $activeWorkersCount = $workers
            ->where('status', 'active')
            ->where('shift_status', 'active')
            ->count();

        return [
            'workers' => $workers,
            'activeWorkersCount' => $activeWorkersCount,
        ];
    };
    
    // FT Admin Dashboard
    Route::get('/dashboard', function () use ($buildRevenueSummary, $buildStaffDirectorySummary) {
        $user = Auth::user();
        $truck = $user->foodTruck;

        $staffSummary = $buildStaffDirectorySummary($user->foodtruck_id);
        $ftworkers = $staffSummary['workers'];
        $activeWorkersCount = $staffSummary['activeWorkersCount'];

        $menuItems = \App\Models\Menu::with('optionGroups.choices')
            ->where('foodtruck_id', $user->foodtruck_id)
            ->orderBy('category', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $revenueSummary = $buildRevenueSummary($user->foodtruck_id);
        $completedRevenueRows = $revenueSummary['completedRevenueRows'];
        $totalRevenueAmount = $revenueSummary['totalRevenueAmount'];
        $completedOrdersCount = $revenueSummary['completedOrdersCount'];

        $isAccountActive = ($user->status ?? null) === 'active';
        $isTruckApproved = $truck && ($truck->status ?? null) === 'approved';
        $isOperational = ($isAccountActive && $isTruckApproved)
            ? (bool) $truck->is_operational
            : false;

        $punchLogRange = request()->query('punch_log_range', 'all');
        $punchLogsQuery = WorkerPunchCard::query()
            ->with('worker:id,full_name,email')
            ->where('foodtruck_id', $user->foodtruck_id);

        if ($punchLogRange === 'today') {
            $punchLogsQuery->whereDate('punched_in_at', now()->toDateString());
        } elseif ($punchLogRange === 'week') {
            $punchLogsQuery->whereBetween('punched_in_at', [
                now()->copy()->startOfWeek(),
                now()->copy()->endOfWeek(),
            ]);
        }

        $punchCardLogs = $punchLogsQuery
            ->orderByDesc('punched_in_at')
            ->limit(100)
            ->get();

        return view('ftadmin.ftadmin-dashboard', compact(
            'ftworkers',
            'menuItems',
            'completedRevenueRows',
            'totalRevenueAmount',
            'completedOrdersCount',
            'isOperational',
            'punchCardLogs',
            'punchLogRange',
            'activeWorkersCount'
        ));
    })->name('dashboard');

    Route::get('/revenue-summary', function () use ($buildRevenueSummary) {
        $user = Auth::user();
        $revenueSummary = $buildRevenueSummary($user->foodtruck_id);

        return response()->json([
            'success' => true,
            'completed_revenue_rows' => $revenueSummary['completedRevenueRows'],
            'total_revenue_amount' => $revenueSummary['totalRevenueAmount'],
            'completed_orders_count' => $revenueSummary['completedOrdersCount'],
        ]);
    })->name('revenue-summary');

    Route::get('/staff-directory-summary', function () use ($buildStaffDirectorySummary) {
        $user = Auth::user();
        $staffSummary = $buildStaffDirectorySummary($user->foodtruck_id);

        return response()->json([
            'success' => true,
            'workers' => $staffSummary['workers'],
            'active_workers_count' => $staffSummary['activeWorkersCount'],
        ]);
    })->name('staff-directory-summary');

    // Manage Menus
    Route::get('/menus', function () {
        $user = Auth::user();
        $menuItems = \App\Models\Menu::with('optionGroups.choices')
            ->where('foodtruck_id', $user->foodtruck_id)
            ->orderBy('category', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        return view('ftadmin.manage-menus', compact('menuItems'));
    })->name('menus');

    // New Orders
    Route::get('/new-orders', function () {
        return view('ftadmin.order-tracking');
    })->name('new-orders');

    // Order Tracking (legacy path)
    Route::get('/orders', function () {
        return view('ftadmin.order-tracking');
    })->name('orders');

    // Reviews & Ratings
    Route::get('/reviews', function () {
        return view('ftadmin.reviews-ratings');
    })->name('reviews');

    // Truck Operational Toggle
    Route::post('/toggle-operational', function () {
        $user = Auth::user();
        $truck = \App\Models\FoodTruck::find($user->foodtruck_id);
        if (!$truck) return response()->json(['success' => false], 404);

        if (($user->status ?? null) !== 'active' || ($truck->status ?? null) !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Truck operational status can only be changed after approval.',
                'is_operational' => false,
            ], 403);
        }

        $updatedOperational = null;
        $autoRejectedCount = 0;

        DB::transaction(function () use ($truck, &$updatedOperational, &$autoRejectedCount) {
            $truck->is_operational = !$truck->is_operational;
            $truck->save();
            $updatedOperational = (bool) $truck->is_operational;

            if ($updatedOperational) {
                return;
            }

            $offlineRefundNote = 'Truck is currently offline. Your paid order was rejected and refund processing has started.';
            $rejectableStatuses = ['pending', 'accepted', 'preparing', 'prepared', 'ready_for_pickup', 'delivery'];

            $autoRejectedCount = Order::query()
                ->where('foodtruck_id', $truck->id)
                ->whereIn('status', $rejectableStatuses)
                ->update([
                    'status' => 'rejected',
                    'accepted_by' => null,
                    'notes' => $offlineRefundNote,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'success' => true,
            'is_operational' => (bool) $updatedOperational,
            'auto_rejected_orders' => (int) $autoRejectedCount,
        ]);
    })->name('toggle-operational');

    // Truck Profile
    Route::get('/truck-profile', function () {
        $user = Auth::user();
        $truck = \App\Models\FoodTruck::find($user->foodtruck_id);
        if (!$truck) return response()->json(['success' => false, 'message' => 'Truck not found'], 404);
        return response()->json([
            'success' => true,
            'truck' => [
                'id' => $truck->id,
                'foodtruck_name' => $truck->foodtruck_name,
                'business_license_no' => $truck->business_license_no,
                'foodtruck_desc' => $truck->foodtruck_desc
            ]
        ]);
    })->name('truck-profile.get');

    Route::post('/truck-profile', function () {
        $user = Auth::user();
        $truck = \App\Models\FoodTruck::find($user->foodtruck_id);
        if (!$truck) return response()->json(['success' => false, 'message' => 'Truck not found'], 404);
        
        $validated = request()->validate([
            'foodtruck_name' => 'required|string|max:255',
            'business_license_no' => 'required|string|max:255',
            'foodtruck_desc' => 'nullable|string|max:1000'
        ]);
        
        $truck->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Truck profile updated successfully',
            'truck' => [
                'id' => $truck->id,
                'foodtruck_name' => $truck->foodtruck_name,
                'business_license_no' => $truck->business_license_no,
                'foodtruck_desc' => $truck->foodtruck_desc
            ]
        ]);
    })->name('truck-profile.update');

    // Staff Management
    Route::post('/register-staff', [StaffController::class, 'store'])->name('register.staff');
    Route::get('/staff/{id}/details', [StaffController::class, 'details'])->name('staff.details');
    Route::post('/staff/{id}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');
    Route::post('/staff/{id}/fire', [StaffController::class, 'fire'])->name('staff.fire');
    Route::delete('/staff/{id}', [StaffController::class, 'delete'])->name('staff.delete');

    // Menu List Operations
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::post('/store', [MenuController::class, 'store'])->name('store');
        Route::put('/{id}', [MenuController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-status', [MenuController::class, 'toggleStatus'])->name('toggle-status');
        Route::patch('/{id}/details', [MenuController::class, 'updateDetails'])->name('update-details');
        Route::patch('/{id}/quantity', [MenuController::class, 'updateQuantity'])->name('update-quantity');
        Route::patch('/{id}/options', [MenuController::class, 'updateOptions'])->name('update-options');
        Route::delete('/{id}', [MenuController::class, 'destroy'])->name('destroy');
    });

    // Category Management
    Route::post('/menu-category/create', [MenuController::class, 'createCategory'])->name('menu-category.create');
    Route::get('/menu-category/list', [MenuController::class, 'getCategories'])->name('menu-category.list');
    Route::patch('/menu-category/{id}', [MenuController::class, 'updateCategory'])->name('menu-category.update');
    Route::delete('/menu-category/{id}', [MenuController::class, 'deleteCategory'])->name('menu-category.delete');
});

/**
 * Food Truck Worker Routes (ftworker)
 */
Route::middleware(['auth', 'role:3', 'ftworker.status'])->prefix('ftworker')->name('ftworker.')->group(function () {
    Route::get('/truck-operational-status', function () {
        $user = Auth::user();
        $truck = $user?->foodTruck()->select('id', 'status', 'is_operational')->first();

        if (!$truck) {
            return response()->json([
                'success' => false,
                'message' => 'Truck not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'truck_status' => $truck->status,
            'is_operational' => (bool) $truck->is_operational,
        ]);
    })->name('truck-operational-status');

    Route::get('/dashboard', function () {
        $user = Auth::user();

        $pendingOrdersCount = Order::query()
            ->where('foodtruck_id', $user->foodtruck_id)
            ->where('status', 'pending')
            ->count();

        $completedTodayCount = Order::query()
            ->where('accepted_by', $user->id)
            ->where('status', 'done')
            ->whereDate('updated_at', now()->toDateString())
            ->count();

        $activePunchCard = WorkerPunchCard::query()
            ->where('user_id', $user->id)
            ->whereNull('punched_out_at')
            ->latest('punched_in_at')
            ->first();

        $latestPunchCard = WorkerPunchCard::query()
            ->where('user_id', $user->id)
            ->latest('punched_in_at')
            ->first();

        return view('ftworker.ftworker-dashboard', compact(
            'pendingOrdersCount',
            'completedTodayCount',
            'activePunchCard',
            'latestPunchCard'
        ));
    })->name('dashboard');

    Route::get('/new-orders', function () {
        $user = Auth::user();
        $isPunchedIn = WorkerPunchCard::query()
            ->where('user_id', $user->id)
            ->whereNull('punched_out_at')
            ->exists();

        return view('ftworker.new-orders', compact('isPunchedIn'));
    })->name('new-orders');

    Route::post('/punch-card/in', [WorkerPunchCardController::class, 'punchIn'])->name('punch-card.in');
    Route::post('/punch-card/out', [WorkerPunchCardController::class, 'punchOut'])->name('punch-card.out');
});

/**
 * Shared Order Routes (ftworker + ftadmin, same truck)
 */
Route::middleware(['auth', 'role:2,3', 'ftworker.status'])->prefix('orders')->name('orders.')->group(function () {
    Route::get('/pending', [OrderController::class, 'pending'])->name('pending');
    Route::get('/my-activity', [OrderController::class, 'myActivity'])->name('my-activity');
    Route::post('/{id}/accept', [OrderController::class, 'accept'])->name('accept');
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->name('update-status');
});

require __DIR__.'/auth.php';