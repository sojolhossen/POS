<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * Display a listing of staff accounts (Super Admin only).
     */
    public function index(Request $request): View
    {
        $query = User::with('store')->latest();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $staff = $query->paginate(15)->withQueryString();
        $stores = Store::active()->orderBy('name')->get();

        return view('staff.index', compact('staff', 'stores'));
    }

    /**
     * Show the form for creating a new staff account.
     */
    public function create(): View
    {
        $stores = Store::active()->orderBy('name')->get();
        $roles = UserRole::cases();

        return view('staff.create', compact('stores', 'roles'));
    }

    /**
     * Store a newly created staff account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'store_id' => ['nullable', 'exists:stores,id'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers()->symbols(),
            ],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'store_id' => $validated['store_id'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::log(
            action: 'staff.created',
            auditable: $user,
            oldValues: null,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'store_id' => $user->store_id,
                'is_active' => $user->is_active,
            ],
            reason: 'Super Admin created staff account'
        );

        return redirect()->route('staff.index')->with('success', 'Staff account created successfully.');
    }

    /**
     * Show the form for editing the specified staff account.
     */
    public function edit(User $staff): View
    {
        $stores = Store::active()->orderBy('name')->get();
        $roles = UserRole::cases();

        return view('staff.edit', compact('staff', 'stores', 'roles'));
    }

    /**
     * Update the specified staff account.
     */
    public function update(Request $request, User $staff): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'store_id' => ['nullable', 'exists:stores,id'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $staff->only(['name', 'email', 'phone', 'role', 'store_id', 'is_active']);
        $newActive = $request->boolean('is_active', true);

        // If deactivating, immediately revoke active session
        $wasDeactivated = $staff->is_active && !$newActive;

        $staff->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'store_id' => $validated['store_id'] ?? null,
            'is_active' => $newActive,
        ]);

        if ($wasDeactivated) {
            $staff->current_session_id = null;
        }

        $staff->save();

        AuditService::log(
            action: $wasDeactivated ? 'staff.deactivated' : 'staff.updated',
            auditable: $staff,
            oldValues: $oldValues,
            newValues: $staff->only(['name', 'email', 'phone', 'role', 'store_id', 'is_active']),
            reason: $wasDeactivated ? 'Account deactivated by Super Admin' : 'Staff details updated'
        );

        return redirect()->route('staff.index')->with('success', 'Staff account updated successfully.');
    }

    /**
     * Reset password for staff account (Super Admin only).
     */
    public function resetPassword(Request $request, User $staff): RedirectResponse
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers()->symbols(),
            ],
        ]);

        $staff->password = Hash::make($validated['password']);
        $staff->current_session_id = null; // Revoke active session so user re-authenticates
        $staff->save();

        AuditService::log(
            action: 'staff.password_reset',
            auditable: $staff,
            reason: 'Password reset by Super Admin'
        );

        return redirect()->route('staff.index')->with('success', "Password for {$staff->name} has been reset.");
    }
}
