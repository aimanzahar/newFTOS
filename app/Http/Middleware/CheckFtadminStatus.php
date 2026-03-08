<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckFtadminStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->role == 2 && $user->status == 'pending') {
            // If pending ftadmin tries to access other ftadmin routes, redirect to dashboard
            if (!$request->routeIs('ftadmin.dashboard')) {
                return redirect()->route('ftadmin.dashboard');
            }
        }

        return $next($request);
    }
}
