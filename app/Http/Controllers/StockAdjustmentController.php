<?php

namespace App\Http\Controllers;

use App\Enums\StockAdjustmentStatus;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of stock adjustments.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = StockAdjustment::with(['store', 'variant.product', 'requester', 'approver'])
            ->latest('id');

        if (!$user->isSuperAdmin()) {
            $query->where('store_id', $user->store_id);
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $adjustments = $query->paginate(15)->withQueryString();
        $stores = Store::active()->orderBy('name')->get();

        return view('stock_adjustments.index', compact('adjustments', 'stores'));
    }

    /**
     * Show form for proposing a new stock adjustment.
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        $storesQuery = Store::active()->orderBy('name');
        if (!$user->isSuperAdmin()) {
            $storesQuery->where('id', $user->store_id);
        }
        $stores = $storesQuery->get();

        $variants = ProductVariant::with('product')->active()->get();

        return view('stock_adjustments.create', compact('stores', 'variants'));
    }

    /**
     * Store a proposed stock adjustment. Does NOT alter balance until approved!
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'proposed_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        if (!$user->canAccessStore($validated['store_id'])) {
            abort(403, __('messages.unauthorized_store_access'));
        }

        $adjustment = InventoryService::requestAdjustment(
            storeId: (int) $validated['store_id'],
            variantId: (int) $validated['product_variant_id'],
            proposedQuantity: (int) $validated['proposed_quantity'],
            reason: $validated['reason'],
            requester: $user
        );

        return redirect()->route('stock-adjustments.index')
            ->with('success', __('messages.adjustment_pending_approval'));
    }

    /**
     * Approve stock adjustment (Super Admin ONLY). Modifies stock ledger and balance.
     */
    public function approve(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            abort(403, 'Only the Super Admin may approve stock adjustments.');
        }

        try {
            InventoryService::approveAdjustment($adjustment, $user);
            return redirect()->route('stock-adjustments.index')
                ->with('success', 'Stock adjustment approved and stock balance updated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject stock adjustment (Super Admin ONLY).
     */
    public function reject(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            abort(403, 'Only the Super Admin may reject stock adjustments.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        InventoryService::rejectAdjustment($adjustment, $user, $validated['rejection_reason']);

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment rejected.');
    }
}
