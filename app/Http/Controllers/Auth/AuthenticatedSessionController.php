<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    // Role Definitions matching your database logic
    const ROLE_CUSTOMER = 1;
    const ROLE_FOOD_TRUCK_ADMIN = 2;
    const ROLE_FOOD_TRUCK_WORKER = 3;
    const ROLE_SYSTEM_ADMIN = 6;

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $role = intval($user->role);

        /**
         * ROLE-BASED REDIRECTION
         * We redirect users to their specific dashboard route names 
         * defined in routes/web.php.
         */
        return match ($role) {
            // System Admin (Route name is admin.dashboard)
            self::ROLE_SYSTEM_ADMIN => redirect(route('admin.dashboard')),
            
            // Food Truck Admin (Route name is ftadmin.dashboard)
            self::ROLE_FOOD_TRUCK_ADMIN => redirect(route('ftadmin.dashboard')),

            // Food Truck Worker (Route name is ftworker.dashboard)
            self::ROLE_FOOD_TRUCK_WORKER => redirect()->intended(route('ftworker.dashboard')),

            // Customer
            self::ROLE_CUSTOMER => redirect()->intended(route('customer.dashboard')),

            // Default fallback
            default => redirect()->intended(route('dashboard')),
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}