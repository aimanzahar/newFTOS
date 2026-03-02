<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TruckApprovalController;
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
    Route::get('/dashboard', function () {
        // Fetch ftworkers (role 3) directly here for the modal
        $user = Auth::user();
        
        $ftworkers = \App\Models\User::where('role', 3)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->get();
            
        return view('ftadmin.ftadmin-dashboard', compact('ftworkers'));
    })->name('dashboard');
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