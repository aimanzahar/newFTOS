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

            if (in_array($status, ['pending', 'rejected', 'deactivated', 'fired'], true)) {
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
