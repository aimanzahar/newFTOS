<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TruckApprovalController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\WorkerPunchCardController;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkerPunchCard;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

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
});

/**
 * Food Truck Admin Routes (ftadmin)
 */
Route::middleware(['auth', 'role:2', 'ftadmin.status'])->prefix('ftadmin')->name('ftadmin.')->group(function () {
    
    // FT Admin Dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();
        $truck = $user->foodTruck;
        $rawFtworkers = \App\Models\User::where('role', 3)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->orderBy('full_name', 'asc')
            ->get();

        $workerIds = $rawFtworkers->pluck('id');
        $activePunchCardsByWorkerId = WorkerPunchCard::query()
            ->where('foodtruck_id', $user->foodtruck_id)
            ->whereIn('user_id', $workerIds)
            ->whereNull('punched_out_at')
            ->orderByDesc('punched_in_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($cards) => $cards->first());

        $ftworkers = $rawFtworkers->map(function ($worker) use ($activePunchCardsByWorkerId) {
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

        $activeWorkersCount = $ftworkers
            ->where('status', 'active')
            ->where('shift_status', 'active')
            ->count();

        $menuItems = \App\Models\Menu::with('optionGroups.choices')
            ->where('foodtruck_id', $user->foodtruck_id)
            ->orderBy('category', 'asc')
            ->orderBy('name', 'asc')
            ->get();

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
            'isOperational',
            'punchCardLogs',
            'punchLogRange',
            'activeWorkersCount'
        ));
    })->name('dashboard');

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

        $truck->is_operational = !$truck->is_operational;
        $truck->save();
        return response()->json(['success' => true, 'is_operational' => $truck->is_operational]);
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