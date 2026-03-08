<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TruckApprovalController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('register', [RegisteredUserController::class, 'create'])
    ->name('register');

// Standard User (Customer) Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/**
 * Customer Routes
 */
Route::middleware(['auth'])->prefix('customer')->name('customer.')->group(function () {
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
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * Admin Routes (Super Admin)
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/pending', [AdminController::class, 'pendingTrucks'])->name('pending.trucks');
    Route::post('/approve-user/{id}', [AdminController::class, 'approveUser'])->name('approve.user');
    Route::post('/trucks/{id}/approve', [TruckApprovalController::class, 'approve'])->name('approve-truck');
    Route::delete('/trucks/{id}/reject', [TruckApprovalController::class, 'reject'])->name('reject-truck');
});

/**
 * Food Truck Admin Routes (ftadmin)
 */
Route::middleware(['auth'])->prefix('ftadmin')->name('ftadmin.')->group(function () {
    
    // FT Admin Dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();
        $ftworkers = \App\Models\User::where('role', 3)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->get();
        $menuItems = \App\Models\Menu::with('optionGroups.choices')
            ->where('foodtruck_id', $user->foodtruck_id)
            ->orderBy('category', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $isOperational = $user->foodTruck ? (bool) $user->foodTruck->is_operational : true;
        return view('ftadmin.ftadmin-dashboard', compact('ftworkers', 'menuItems', 'isOperational'));
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

    // Order Tracking
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
        $truck->is_operational = !$truck->is_operational;
        $truck->save();
        return response()->json(['success' => true, 'is_operational' => $truck->is_operational]);
    })->name('toggle-operational');

    // Staff Management
    Route::post('/register-staff', [StaffController::class, 'store'])->name('register.staff');
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
});

/**
 * Food Truck Worker Routes (ftworker)
 */
Route::middleware(['auth'])->prefix('ftworker')->name('ftworker.')->group(function () {
    Route::get('/dashboard', function () {
        return view('ftworker.ftworker-dashboard');
    })->name('dashboard');

    Route::get('/new-orders', function () {
        return view('ftworker.new-orders');
    })->name('new-orders');
});

/**
 * Shared Order Routes (ftworker + ftadmin, same truck)
 */
Route::middleware(['auth'])->prefix('orders')->name('orders.')->group(function () {
    Route::get('/pending', [OrderController::class, 'pending'])->name('pending');
    Route::get('/my-activity', [OrderController::class, 'myActivity'])->name('my-activity');
    Route::post('/{id}/accept', [OrderController::class, 'accept'])->name('accept');
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->name('update-status');
});

require __DIR__.'/auth.php';