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

        // 1. Stock Balances with Search & Filters
        $search = trim($request->input('search', ''));
        $stockStatus = $request->input('stock_status', 'all');

        $stockQuery = $store->stockBalances()
            ->with(['variant.product.category']);

        if ($search !== '') {
            $stockQuery->whereHas('variant', function ($vQ) use ($search) {
                $vQ->where('barcode', 'LIKE', "%{$search}%")
                    ->orWhere('size', 'LIKE', "%{$search}%")
                    ->orWhere('color', 'LIKE', "%{$search}%")
                    ->orWhereHas('product', function ($pQ) use ($search) {
                        $pQ->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('sku', 'LIKE', "%{$search}%")
                            ->orWhere('brand', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($stockStatus === 'in_stock') {
            $stockQuery->where('quantity_on_hand', '>', 5);
        } elseif ($stockStatus === 'low_stock') {
            $stockQuery->where('quantity_on_hand', '>', 0)
                ->where('quantity_on_hand', '<=', 5);
        } elseif ($stockStatus === 'out_of_stock') {
            $stockQuery->where('quantity_on_hand', '<=', 0);
        }

        $stockBalances = $stockQuery->orderBy('quantity_on_hand', 'desc')
            ->paginate(15)
            ->withQueryString();

        // 2. High-level Inventory KPI Aggregates for this store
        $allBalances = $store->stockBalances()->with('variant.product')->get();
        $totalSkus = $allBalances->count();
        $totalUnitsOnHand = (int) $allBalances->sum('quantity_on_hand');
        $totalUnitsReserved = (int) $allBalances->sum('quantity_reserved');
        $totalUnitsAvailable = (int) $allBalances->sum(fn ($b) => $b->available_quantity);
        $lowStockCount = $allBalances->filter(fn ($b) => $b->quantity_on_hand > 0 && $b->quantity_on_hand <= 5)->count();
        $outOfStockCount = $allBalances->filter(fn ($b) => $b->quantity_on_hand <= 0)->count();

        // Valuation
        $retailValuation = $allBalances->sum(function ($b) {
            $price = $b->variant?->effective_selling_price ?? 0;
            return $b->quantity_on_hand * $price;
        });

        $costValuation = $allBalances->sum(function ($b) {
            $cost = $b->variant?->effective_purchase_price ?? 0;
            return $b->quantity_on_hand * $cost;
        });

        // 3. Retail Sales & Register Metrics
        $todaySalesAmount = 0;
        $todaySalesCount = 0;
        $totalSalesAmount = 0;
        $activeRegister = null;
        $recentSales = collect();

        if (!$store->is_warehouse) {
            $todaySalesAmount = (float) $store->posSales()
                ->where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('total_payable');

            $todaySalesCount = $store->posSales()
                ->where('status', 'completed')
                ->whereDate('created_at', today())
                ->count();

            $totalSalesAmount = (float) $store->posSales()
                ->where('status', 'completed')
                ->sum('total_payable');

            $activeRegister = $store->dailyCashRegisters()
                ->where('status', 'open')
                ->with('opener')
                ->latest()
                ->first();

            $recentSales = $store->posSales()
                ->with(['cashier', 'customer'])
                ->latest()
                ->limit(6)
                ->get();
        }

        // 4. Logistics & Transfers
        $recentTransfers = \App\Models\StockTransfer::with(['sourceStore', 'destinationStore', 'creator'])
            ->where(function ($q) use ($store) {
                $q->where('source_store_id', $store->id)
                    ->orWhere('destination_store_id', $store->id);
            })
            ->latest()
            ->limit(6)
            ->get();

        $inTransitInbound = \App\Models\StockTransfer::where('destination_store_id', $store->id)
            ->where('status', 'in_transit')
            ->count();

        $inTransitOutbound = \App\Models\StockTransfer::where('source_store_id', $store->id)
            ->where('status', 'in_transit')
            ->count();

        // 5. Staff
        $staffMembers = $store->staff()->where('is_active', true)->get();

        return view('stores.show', compact(
            'store',
            'stockBalances',
            'totalSkus',
            'totalUnitsOnHand',
            'totalUnitsReserved',
            'totalUnitsAvailable',
            'lowStockCount',
            'outOfStockCount',
            'retailValuation',
            'costValuation',
            'todaySalesAmount',
            'todaySalesCount',
            'totalSalesAmount',
            'activeRegister',
            'recentSales',
            'recentTransfers',
            'inTransitInbound',
            'inTransitOutbound',
            'staffMembers'
        ));
    }
}
