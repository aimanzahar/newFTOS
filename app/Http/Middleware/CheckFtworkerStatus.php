<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckFtworkerStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || (int) $user->role !== 3) {
            return $next($request);
        }

        $worker = $user->fresh(['foodTruck.owner']) ?? $user;

        $workerBlocked = in_array($worker->status, ['deactivated', 'fired'], true);
        $owner = $worker->foodTruck?->owner;
        $ownerBlockedBySystemAdmin = $owner
            && in_array($owner->status, ['deactivated', 'fired'], true)
            && (bool) $owner->status_locked_by_system_admin;

        if (!$workerBlocked && !$ownerBlockedBySystemAdmin) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is currently restricted.',
            ], 403);
        }

        if (!$request->routeIs('ftworker.dashboard')) {
            return redirect()->route('ftworker.dashboard');
        }

        return $next($request);
    }
}
