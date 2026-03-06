<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FTAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Adjust the role check (e.g., 2) based on your database logic
        if (Auth::check() && Auth::user()->role == 2) {
            return $next($request);
        }

        return redirect('/dashboard')->with('error', 'Unauthorized access.');
    }
}