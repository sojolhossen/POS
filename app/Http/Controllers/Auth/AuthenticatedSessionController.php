<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // 1. Validate credentials
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            AuditService::log(
                action: 'auth.failed_login',
                auditable: null,
                oldValues: null,
                newValues: ['email' => $credentials['email']],
                reason: 'Failed login attempt with incorrect credentials'
            );

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // 2. Validate active account status
        if (!$user->is_active) {
            AuditService::log(
                action: 'auth.login_denied_deactivated',
                auditable: $user,
                oldValues: ['is_active' => false],
                newValues: null,
                reason: 'Login attempt on deactivated staff account'
            );

            throw ValidationException::withMessages([
                'email' => __('messages.account_deactivated'),
            ]);
        }

        // 3. Authenticate and enforce Single Active Session
        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        $sessionId = $request->session()->getId();
        $user->current_session_id = $sessionId;
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        // Sync user locale preference to session
        if ($user->locale) {
            $request->session()->put('locale', $user->locale);
        }

        AuditService::log(
            action: 'auth.login_success',
            auditable: $user,
            oldValues: null,
            newValues: ['session_id' => $sessionId, 'ip' => $request->ip()],
            reason: 'Successful user authentication',
            actor: $user
        );

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $user->current_session_id = null;
            $user->save();

            AuditService::log(
                action: 'auth.logout',
                auditable: $user,
                reason: 'User logged out',
                actor: $user
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
