<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleActiveSession
{
    /**
     * Handle an incoming request.
     * Enforces:
     * 1. Immediate lockout if user account is deactivated.
     * 2. Single active session per account — revokes previous session on concurrent login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Account Deactivation check
            if (!$user->is_active) {
                AuditService::log(
                    action: 'auth.locked_out_deactivated',
                    auditable: $user,
                    reason: 'Attempted access while account is deactivated'
                );

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => __('messages.account_deactivated')], 403);
                }

                return redirect()->route('login')->withErrors([
                    'email' => __('messages.account_deactivated'),
                ]);
            }

            // 2. Single Active Session enforcement
            $currentSessionId = $request->session()->getId();
            if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
                AuditService::log(
                    action: 'auth.session_revoked_concurrent',
                    auditable: $user,
                    reason: 'Logged in from another device/browser session'
                );

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => __('messages.session_revoked_by_new_login')], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => __('messages.session_revoked_by_new_login'),
                ]);
            }
        }

        return $next($request);
    }
}
