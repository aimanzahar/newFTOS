<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController; 
use App\Http\Controllers\AdminController;    
use App\Http\Controllers\Admin\TruckApprovalController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('register', [RegisteredUserController::class, 'create'])
    ->name('register');

// Standard User Dashboard
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
 * Note: The 'admin.' name prefix is applied to EVERYTHING inside this group.
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // URL: /admin/dashboard -> Route Name: admin.dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // URL: /admin/pending -> Route Name: admin.pending.trucks
    Route::get('/pending', [AdminController::class, 'pendingTrucks'])->name('pending.trucks');

    // URL: /admin/approve-user/{id} -> Route Name: admin.approve.user
    Route::post('/approve-user/{id}', [AdminController::class, 'approveUser'])->name('approve.user');

    // URL: /admin/trucks/{id}/approve -> Route Name: admin.approve-truck
    Route::post('/trucks/{id}/approve', [TruckApprovalController::class, 'approve'])
        ->name('approve-truck');

    // URL: /admin/trucks/{id}/reject -> Route Name: admin.reject-truck
    Route::delete('/trucks/{id}/reject', [TruckApprovalController::class, 'reject'])
        ->name('reject-truck');
});

require __DIR__.'/auth.php';