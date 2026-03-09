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

        if ($user && (int) $user->role === 2) {
            $status = $user->fresh()?->status ?? $user->status;

            if ($status === 'rejected') {
                // Log out rejected users and redirect to login
                if (!$request->routeIs('logout')) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return redirect('/login')->with('error', 'Your registration has been rejected and your access has been revoked.');
                }
                
                // Allow logout request to proceed
                return $next($request);
            }

            if ($status === 'pending') {
                // Allow logout so the user is not trapped
                if ($request->routeIs('logout')) {
                    return $next($request);
                }

                // For any other route, force the ftadmin dashboard
                if (!$request->routeIs('ftadmin.dashboard')) {
                    return redirect()->route('ftadmin.dashboard');
                }
            }
        }

        return $next($request);
    }
}
