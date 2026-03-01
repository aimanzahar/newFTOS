<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in and if their role is 6 (Admin)
        // Adjust the '6' if your system uses a different number for Super Admin
        if (Auth::check() && Auth::user()->role === 6) {
            return $next($request);
        }

        // If not admin, kick them back to the home page or dashboard
        return redirect('/')->with('error', 'You do not have administrator access.');
    }
}