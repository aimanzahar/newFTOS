<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * Usage examples:
     * - role:1
     * - role:2,3
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = (string) Auth::user()->role;
        $allowedRoles = array_map('strval', $roles);

        if (empty($allowedRoles) || in_array($userRole, $allowedRoles, true)) {
            return $next($request);
        }

        return redirect($this->resolveHomePath((int) $userRole))
            ->with('error', 'Unauthorized access for your account role.');
    }

    private function resolveHomePath(int $role): string
    {
        return match ($role) {
            6 => '/admin/dashboard',
            2 => '/ftadmin/dashboard',
            3 => '/ftworker/dashboard',
            1 => '/customer/dashboard',
            default => '/',
        };
    }
}
