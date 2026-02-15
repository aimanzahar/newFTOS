<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController; 
use App\Http\Controllers\AdminController;    

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
 * Note: Grouping with 'admin' prefix means the URLs inside don't need 'admin' repeated.
 */
Route::middleware(['auth'])->prefix('admin')->group(function () {
    
    // URL: /admin/dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    // URL: /admin/pending (Matches your Controller method)
    Route::get('/pending', [AdminController::class, 'pendingTrucks'])->name('admin.pending.trucks');

    // URL: /admin/approve-user/{id}
    Route::post('/approve-user/{id}', [AdminController::class, 'approveUser'])->name('admin.approve.user');
});

require __DIR__.'/auth.php';