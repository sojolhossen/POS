<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceModulePermission
{
    /**
     * Handle an incoming request.
     * Checks if current authenticated user has permission to access the given module.
     * Super Admin bypasses all checks.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->canAccessModule($module)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access Denied: This module is locked by the Store Owner (Super Admin).'
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Access Denied: The "' . ucwords(str_replace('_', ' ', $module)) . '" module is currently locked by the Store Owner (Super Admin).');
        }

        return $next($request);
    }
}
