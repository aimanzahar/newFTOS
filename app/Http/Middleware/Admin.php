<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in AND if their role is 'admin'
        // Adjust 'admin' to match the exact string or ID in your 'role' column
        if (Auth::check() && Auth::user()->role == 'admin') {
            return $next($request);
        }

        // If not admin, redirect to standard dashboard or home
        return redirect('/dashboard')->with('error', 'You do not have admin access.');
    }
}