<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\Store;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockCountController extends Controller
{
    /**
     * Display listing of stock counts.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = StockCount::with(['store', 'creator'])->latest('id');

        if (!$user->isSuperAdmin()) {
            $query->where('store_id', $user->store_id);
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $counts = $query->paginate(15);
        $stores = Store::active()->orderBy('name')->get();

        return view('stock_counts.index', compact('counts', 'stores'));
    }

    /**
     * Show form for initiating a physical stock count session.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        $storesQuery = Store::active()->orderBy('name');
        if (!$user->isSuperAdmin()) {
            $storesQuery->where('id', $user->store_id);
        }
        $stores = $storesQuery->get();

        $referenceNumber = 'STC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        return view('stock_counts.create', compact('stores', 'referenceNumber'));
    }

    /**
     * Store new stock count session.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'reference_number' => ['required', 'string', 'unique:stock_counts,reference_number'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (!$user->canAccessStore($validated['store_id'])) {
            abort(403, __('messages.unauthorized_store_access'));
        }

        $count = StockCount::create([
            'store_id' => $validated['store_id'],
            'reference_number' => $validated['reference_number'],
            'status' => 'in_progress',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        AuditService::log(
            action: 'stock.count_started',
            auditable: $count,
            reason: 'Physical stock count session initialized'
        );

        return redirect()->route('stock-counts.show', $count)->with('success', 'Physical stock count session started.');
    }

    /**
     * Show physical count workstation with barcode scanner and item grid.
     */
    public function show(StockCount $stockCount, Request $request): View
    {
        $user = $request->user();
        if (!$user->canAccessStore($stockCount->store_id)) {
            abort(403, __('messages.unauthorized_store_access'));
        }

        $stockCount->load(['store', 'creator', 'items.variant.product']);

        return view('stock_counts.show', compact('stockCount'));
    }

    /**
     * Record or update counted item via barcode scan or manual entry.
     */
    public function recordItem(Request $request, StockCount $stockCount): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (!$user->canAccessStore($stockCount->store_id)) {
            abort(403, __('messages.unauthorized_store_access'));
        }

        if ($stockCount->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'This count session is closed.'], 422);
        }

        $validated = $request->validate([
            'barcode' => ['nullable', 'string'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'counted_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $variant = null;
        if (!empty($validated['barcode'])) {
            $variant = ProductVariant::where('barcode', trim($validated['barcode']))->first();
        } elseif (!empty($validated['product_variant_id'])) {
            $variant = ProductVariant::find($validated['product_variant_id']);
        }

        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Product variant not found.'], 404);
        }

        // Fetch system quantity on hand
        $balance = StockBalance::where('store_id', $stockCount->store_id)
            ->where('product_variant_id', $variant->id)
            ->first();

        $systemQuantity = $balance ? $balance->quantity_on_hand : 0;
        $countedQuantity = (int) $validated['counted_quantity'];
        $difference = $countedQuantity - $systemQuantity;

        $item = StockCountItem::updateOrCreate(
            [
                'stock_count_id' => $stockCount->id,
                'product_variant_id' => $variant->id,
            ],
            [
                'system_quantity' => $systemQuantity,
                'counted_quantity' => $countedQuantity,
                'difference' => $difference,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'item' => [
                    'id' => $item->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->fullName,
                    'barcode' => $variant->barcode,
                    'system_quantity' => $systemQuantity,
                    'counted_quantity' => $countedQuantity,
                    'difference' => $difference,
                ],
            ]);
        }

        return redirect()->route('stock-counts.show', $stockCount)->with('success', 'Count recorded.');
    }

    /**
     * Complete physical stock count session.
     */
    public function complete(StockCount $stockCount, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user->canAccessStore($stockCount->store_id)) {
            abort(403, __('messages.unauthorized_store_access'));
        }

        $stockCount->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        AuditService::log(
            action: 'stock.count_completed',
            auditable: $stockCount,
            reason: 'Physical stock count completed'
        );

        return redirect()->route('stock-counts.show', $stockCount)->with('success', 'Stock count session completed.');
    }
}
