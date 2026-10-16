<?php

namespace App\Http\Controllers;

use App\Enums\StockTransactionType;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Display stock balances list (properly scoped by store).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $storesQuery = Store::active()->orderBy('name');
        if (!$user->isSuperAdmin()) {
            if ($user->isWarehouseStaff()) {
                $storesQuery->warehouseOnly();
            } else {
                $storesQuery->where('id', $user->store_id);
            }
        }
        $stores = $storesQuery->get();

        // Active selected store
        $selectedStoreId = $request->filled('store_id')
            ? (int) $request->store_id
            : ($user->store_id ?? $stores->first()?->id);

        if ($selectedStoreId && !$user->canAccessStore($selectedStoreId)) {
            abort(403, __('messages.unauthorized_store_access'));
        }

        $query = StockBalance::with(['variant.product.category', 'store'])
            ->where('store_id', $selectedStoreId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('variant', function ($vq) use ($search) {
                $vq->where('barcode', 'LIKE', "%{$search}%")
                    ->orWhere('size', 'LIKE', "%{$search}%")
                    ->orWhere('color', 'LIKE', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('sku', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereRaw('quantity_on_hand <= reorder_level');
        }

        $balances = $query->paginate(15)->withQueryString();
        $selectedStore = $selectedStoreId ? Store::find($selectedStoreId) : null;

        return view('inventory.index', compact('balances', 'stores', 'selectedStore', 'selectedStoreId'));
    }

    /**
     * Display the immutable stock movement ledger.
     */
    public function ledger(Request $request): View
    {
        $user = $request->user();

        $storesQuery = Store::active()->orderBy('name');
        if (!$user->isSuperAdmin()) {
            if ($user->isWarehouseStaff()) {
                $storesQuery->warehouseOnly();
            } else {
                $storesQuery->where('id', $user->store_id);
            }
        }
        $stores = $storesQuery->get();

        $query = StockLedger::with(['variant.product', 'store', 'relatedStore', 'user'])
            ->latest('id');

        if (!$user->isSuperAdmin()) {
            if ($user->isWarehouseStaff()) {
                $warehouseIds = Store::warehouseOnly()->pluck('id')->toArray();
                $query->whereIn('store_id', $warehouseIds);
            } else {
                $query->where('store_id', $user->store_id);
            }
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('variant', function ($vq) use ($search) {
                        $vq->where('barcode', 'LIKE', "%{$search}%")
                            ->orWhereHas('product', function ($pq) use ($search) {
                                $pq->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('sku', 'LIKE', "%{$search}%");
                            });
                    });
            });
        }

        $ledgers = $query->paginate(20)->withQueryString();

        return view('inventory.ledger', compact('ledgers', 'stores'));
    }

    /**
     * Show form for setting initial opening stock balance.
     */
    public function createOpeningStock(): View
    {
        $stores = Store::active()->orderBy('name')->get();
        $variants = ProductVariant::with('product')->active()->get();

        return view('inventory.opening_stock', compact('stores', 'variants'));
    }

    /**
     * Store opening stock balance.
     */
    public function storeOpeningStock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        InventoryService::setOpeningStock(
            variantId: (int) $validated['product_variant_id'],
            storeId: (int) $validated['store_id'],
            quantity: (int) $validated['quantity'],
            unitCost: (float) ($validated['unit_cost'] ?? 0.0),
            notes: $validated['notes'] ?? 'Initial opening stock balance entry'
        );

        return redirect()->route('inventory.index', ['store_id' => $validated['store_id']])
            ->with('success', 'Opening stock balance recorded successfully.');
    }

    /**
     * Show form for receiving goods into central warehouse.
     */
    public function createReceiveWarehouse(): View
    {
        $warehouses = Store::warehouseOnly()->active()->get();
        $variants = ProductVariant::with('product')->active()->get();

        return view('inventory.receive_warehouse', compact('warehouses', 'variants'));
    }

    /**
     * Store warehouse stock receipt.
     */
    public function storeReceiveWarehouse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:stores,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        InventoryService::receiveIntoWarehouse(
            variantId: (int) $validated['product_variant_id'],
            warehouseId: (int) $validated['warehouse_id'],
            quantity: (int) $validated['quantity'],
            unitCost: (float) ($validated['unit_cost'] ?? 0.0),
            referenceNumber: $validated['reference_number'] ?? null,
            notes: $validated['notes'] ?? 'Stock intake to central warehouse'
        );

        return redirect()->route('inventory.index', ['store_id' => $validated['warehouse_id']])
            ->with('success', 'Stock received into warehouse successfully.');
    }
}
