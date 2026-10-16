<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SkuGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreController extends Controller
{
    /**
     * Display a listing of stores (Super Admin views all, Store Manager restricted by middleware/policy).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Store::with('manager');

        if (!$user->isSuperAdmin()) {
            if ($user->isWarehouseStaff()) {
                $query->warehouseOnly();
            } else {
                $query->where('id', $user->store_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $stores = $query->orderBy('is_warehouse', 'desc')->orderBy('name')->paginate(15)->withQueryString();

        return view('stores.index', compact('stores'));
    }

    /**
     * Show form for creating a new store (Super Admin only).
     */
    public function create(): View
    {
        $managers = User::where('role', UserRole::STORE_MANAGER)->active()->orderBy('name')->get();
        $suggestedCode = SkuGeneratorService::generateStoreCode(false);

        return view('stores.create', compact('managers', 'suggestedCode'));
    }

    /**
     * Store a newly created store (Super Admin only).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:stores,code'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'is_warehouse' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $store = Store::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'opening_time' => $validated['opening_time'] ?? null,
            'closing_time' => $validated['closing_time'] ?? null,
            'is_warehouse' => $request->boolean('is_warehouse', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // If a manager was assigned, link store to that user
        if ($store->manager_id) {
            User::where('id', $store->manager_id)->update(['store_id' => $store->id]);
        }

        AuditService::log(
            action: 'store.created',
            auditable: $store,
            newValues: $store->toArray(),
            reason: 'Super Admin created store/warehouse location'
        );

        return redirect()->route('stores.index')->with('success', 'Store created successfully.');
    }

    /**
     * Show form for editing a store (Super Admin only).
     */
    public function edit(Store $store): View
    {
        $managers = User::where('role', UserRole::STORE_MANAGER)->active()->orderBy('name')->get();

        return view('stores.edit', compact('store', 'managers'));
    }

    /**
     * Update a store (Super Admin only).
     */
    public function update(Request $request, Store $store): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('stores')->ignore($store->id)],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'opening_time' => ['nullable'],
            'closing_time' => ['nullable'],
            'is_warehouse' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $old = $store->toArray();

        $store->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'opening_time' => $validated['opening_time'] ?? null,
            'closing_time' => $validated['closing_time'] ?? null,
            'is_warehouse' => $request->boolean('is_warehouse', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($store->manager_id) {
            User::where('id', $store->manager_id)->update(['store_id' => $store->id]);
        }

        AuditService::log(
            action: 'store.updated',
            auditable: $store,
            oldValues: $old,
            newValues: $store->toArray(),
            reason: 'Super Admin updated store record'
        );

        return redirect()->route('stores.index')->with('success', 'Store updated successfully.');
    }

    /**
     * Display a single store detail and its current stock summary.
     */
    public function show(Store $store, Request $request): View
    {
        $user = $request->user();
        if (!$user->canAccessStore($store->id)) {
            abort(403, __('messages.unauthorized_store_access'));
        }

        $store->load(['manager', 'staff']);
        $stockBalances = $store->stockBalances()
            ->with(['variant.product'])
            ->paginate(15);

        return view('stores.show', compact('store', 'stockBalances'));
    }
}
