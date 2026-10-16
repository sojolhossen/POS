<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserRole
{
    /**
     * Handle an incoming request.
     * Enforces that authenticated user possesses one of the declared roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRoleValue = $user->role->value;

        if (!in_array($userRoleValue, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.unauthorized_role_action')], 403);
            }

            abort(403, __('messages.unauthorized_role_action'));
        }

        return $next($request);
    }
}
