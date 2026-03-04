<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TruckApprovalController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\MenuListController; // Added for Menu List features
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

// Standard Authenticated User Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * Admin Routes
 * Prefix: admin/  | Name Prefix: admin.
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Super Admin Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/pending', [AdminController::class, 'pendingTrucks'])->name('pending.trucks');
    Route::post('/approve-user/{id}', [AdminController::class, 'approveUser'])->name('approve.user');
    Route::post('/trucks/{id}/approve', [TruckApprovalController::class, 'approve'])->name('approve-truck');
    Route::delete('/trucks/{id}/reject', [TruckApprovalController::class, 'reject'])->name('reject-truck');
});

/**
 * Food Truck Admin Routes (ftadmin)
 * Prefix: ftadmin/ | Name Prefix: ftadmin.
 */
Route::middleware(['auth'])->prefix('ftadmin')->name('ftadmin.')->group(function () {
    
    // FT Admin Dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();
        
        $ftworkers = \App\Models\User::where('role', 3)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->get();
            
        return view('ftadmin.ftadmin-dashboard', compact('ftworkers'));
    })->name('dashboard');

    // Staff Management
    Route::post('/register-staff', [StaffController::class, 'store'])->name('register.staff');

    /**
     * Menu List Operations
     * Using your preferred naming "menu-list"
     */
    Route::prefix('menu-list')->name('menu_list.')->group(function () {
        // View all menu items
        Route::get('/', [MenuListController::class, 'index'])->name('index');
        // Store a new menu item
        Route::post('/store', [MenuListController::class, 'store'])->name('store');
        // Future routes: update/delete
        Route::put('/{id}', [MenuListController::class, 'update'])->name('update');
        Route::delete('/{id}', [MenuListController::class, 'destroy'])->name('destroy');
    });
});

/**
 * Food Truck Worker Routes (ftworker)
 * Prefix: ftworker/ | Name Prefix: ftworker.
 */
Route::middleware(['auth'])->prefix('ftworker')->name('ftworker.')->group(function () {
    Route::get('/dashboard', function () {
        return view('ftworker.ftworker-dashboard');
    })->name('dashboard');
});

require __DIR__.'/auth.php';