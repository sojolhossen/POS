<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeController extends Controller
{
    /**
     * Display the barcode label printing and scanner workstation with category, stock, and keyword filters.
     */
    public function index(Request $request): View
    {
        $stores = Store::active()->orderBy('name')->get();
        $selectedStore = $request->filled('store_id')
            ? Store::find($request->store_id)
            : ($request->user()->store ?? $stores->first());

        $categories = Category::active()->with('subcategories')->rootOnly()->orderBy('name')->get();

        $query = ProductVariant::with(['product.category', 'product.subcategory', 'stockBalances.store'])
            ->active();

        // Filter by Category or Subcategory
        if ($request->filled('category_id')) {
            $catId = (int) $request->category_id;
            $query->whereHas('product', function ($q) use ($catId) {
                $q->where('category_id', $catId)
                  ->orWhere('subcategory_id', $catId);
            });
        }

        // Search across Style Name, SKU, Barcode, Color, Size, Brand
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('barcode', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%")
                         ->orWhere('brand', 'like', "%{$search}%");
                  });
            });
        }

        // Stock Status filter: 'all', 'in_stock', 'out_of_stock'
        $stockStatus = $request->input('stock_status', 'all');
        $selectedStoreId = $selectedStore?->id;

        if ($stockStatus === 'in_stock') {
            $query->whereHas('stockBalances', function ($sb) use ($selectedStoreId) {
                if ($selectedStoreId) {
                    $sb->where('store_id', $selectedStoreId)->where('quantity_on_hand', '>', 0);
                } else {
                    $sb->where('quantity_on_hand', '>', 0);
                }
            });
        } elseif ($stockStatus === 'out_of_stock') {
            $query->whereDoesntHave('stockBalances', function ($sb) use ($selectedStoreId) {
                if ($selectedStoreId) {
                    $sb->where('store_id', $selectedStoreId)->where('quantity_on_hand', '>', 0);
                } else {
                    $sb->where('quantity_on_hand', '>', 0);
                }
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        if ($perPage <= 0 || $perPage > 200) {
            $perPage = 25;
        }

        $variants = $query->latest('id')->paginate($perPage)->withQueryString();

        return view('barcodes.index', compact('stores', 'selectedStore', 'categories', 'variants'));
    }

    /**
     * Render printable label sheets with configurable dimensions and store name.
     * Supports single variant, entire product, or multi-variant batch array.
     * Note: Reprinting ALWAYS retains the original barcode.
     */
    public function printLabels(Request $request): View
    {
        $storeId = $request->input('store_id');
        $store = !empty($storeId) ? Store::find($storeId) : null;
        $labelWidth = (float) $request->input('label_width', 50);   // Default 50mm
        $labelHeight = (float) $request->input('label_height', 30); // Default 30mm
        $showStoreName = $request->boolean('show_store_name', true);

        $variantIds = [];
        $quantities = [];

        // Scenario 1: Single Variant Quick Print
        if ($request->filled('single_variant_id') || $request->filled('variant_id')) {
            $vId = (int) ($request->input('single_variant_id') ?: $request->input('variant_id'));
            $variantIds = [$vId];
            $qty = max(1, (int) $request->input('single_quantity', $request->input('quantity', 1)));
            $quantities = [$vId => $qty];
        }
        // Scenario 2: Whole Product Quick Print (all variants of a product)
        elseif ($request->filled('product_id')) {
            $product = Product::with(['variants.stockBalances'])->findOrFail((int) $request->input('product_id'));
            $variantIds = $product->variants->pluck('id')->all();
            $useStock = $request->boolean('use_stock_qty', true);

            foreach ($product->variants as $variant) {
                if ($request->has("quantities.{$variant->id}")) {
                    $quantities[$variant->id] = max(1, (int) $request->input("quantities.{$variant->id}"));
                } elseif ($useStock) {
                    $stockQty = $store 
                        ? ($variant->stockBalances->firstWhere('store_id', $store->id)?->quantity_on_hand ?? 0)
                        : (int) $variant->stockBalances->sum('quantity_on_hand');
                    $quantities[$variant->id] = $stockQty > 0 ? $stockQty : 1;
                } else {
                    $quantities[$variant->id] = max(1, (int) $request->input('quantity', 1));
                }
            }
        }
        // Scenario 3: Batch Selection
        else {
            $validated = $request->validate([
                'variant_ids' => ['required', 'array', 'min:1'],
                'variant_ids.*' => ['exists:product_variants,id'],
                'quantities' => ['required', 'array'],
                'store_id' => ['nullable', 'exists:stores,id'],
                'label_width' => ['nullable', 'numeric', 'min:30', 'max:100'],
                'label_height' => ['nullable', 'numeric', 'min:20', 'max:80'],
                'show_store_name' => ['nullable', 'boolean'],
            ]);

            $variantIds = $validated['variant_ids'];
            $quantities = $validated['quantities'];
        }

        $labels = [];
        foreach ($variantIds as $variantId) {
            $variant = ProductVariant::with(['product'])->find($variantId);
            if (!$variant) continue;

            $qty = max(1, (int) ($quantities[$variantId] ?? 1));

            // Generate clean SVG for existing Code 128 barcode
            $barcodeSvg = BarcodeService::renderSvg($variant->barcode);

            // 1. BRAND NAME: Priority given to site brand name configured in Super Admin settings
            $siteBrandName = \App\Models\AppSetting::getAppName();
            $brandName = !empty($siteBrandName)
                ? $siteBrandName
                : (!empty($variant->product->brand) ? $variant->product->brand : ($store ? $store->name : config('app.name', 'FashionPOS')));

            for ($i = 0; $i < $qty; $i++) {
                $labels[] = [
                    'variant' => $variant,
                    'brand_name' => $brandName,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->product->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'price' => $variant->effective_selling_price,
                    'barcode' => $variant->barcode,
                    'barcode_svg' => $barcodeSvg,
                    'store_name' => $store ? $store->name : config('app.name'),
                ];
            }
        }

        return view('barcodes.print', compact('labels', 'labelWidth', 'labelHeight', 'showStoreName', 'store'));
    }

    /**
     * API lookup for camera or hardware scanner: resolves scanned barcode to variant data with complete stock reporting.
     */
    public function scanLookup(Request $request): JsonResponse
    {
        $barcode = trim($request->input('barcode', ''));

        if (empty($barcode)) {
            return response()->json(['success' => false, 'message' => 'Barcode cannot be empty.'], 422);
        }

        $variant = ProductVariant::with(['product.category', 'stockBalances.store'])
            ->where('barcode', $barcode)
            ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => "No product variant found matching barcode: {$barcode}",
            ], 404);
        }

        $user = $request->user();
        $targetStoreId = $request->filled('store_id')
            ? (int) $request->input('store_id')
            : ($user->store_id ?? null);

        // Store-specific balance if store is identified
        $storeBalance = null;
        if ($targetStoreId) {
            $storeBalance = $variant->stockBalances->firstWhere('store_id', $targetStoreId);
        }

        // Total stock across all locations
        $totalStockOnHand = (int) $variant->stockBalances->sum('quantity_on_hand');
        $totalAvailableStock = (int) $variant->stockBalances->sum('available_quantity');

        // Location-wise breakdown for display
        $storesBreakdown = $variant->stockBalances->map(function ($sb) {
            return [
                'store_id' => $sb->store_id,
                'store_name' => $sb->store?->name ?? 'Store #' . $sb->store_id,
                'stock_on_hand' => (int) $sb->quantity_on_hand,
                'available_stock' => (int) $sb->available_quantity,
            ];
        })->values();

        // Effective stock calculations:
        $storeOnHand = $storeBalance ? (int) $storeBalance->quantity_on_hand : null;
        $storeAvailable = $storeBalance ? (int) $storeBalance->available_quantity : null;

        // If specific store has stock balance, use it; if user is at Central/All Stores or no specific store passed,
        // use total enterprise stock so it never incorrectly displays 0 when inventory is available!
        $effectiveStockOnHand = $storeOnHand !== null ? $storeOnHand : $totalStockOnHand;
        $effectiveAvailable = $storeAvailable !== null ? $storeAvailable : $totalAvailableStock;
        $activeStoreName = $storeBalance?->store?->name ?? ($targetStoreId ? 'Selected Store' : 'All Stores (Enterprise Total)');

        return response()->json([
            'success' => true,
            'variant' => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'category_name' => $variant->product->category?->name ?? 'Uncategorized',
                'sku' => $variant->product->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'barcode' => $variant->barcode,
                'selling_price' => $variant->effective_selling_price,
                'selling_price_formatted' => '৳ ' . number_format($variant->effective_selling_price, 2),
                'stock_on_hand' => $effectiveStockOnHand,
                'available_stock' => $effectiveAvailable,
                'store_on_hand' => $storeOnHand,
                'store_available' => $storeAvailable,
                'store_name' => $activeStoreName,
                'total_stock_all_stores' => $totalStockOnHand,
                'stores_breakdown' => $storesBreakdown,
            ],
        ]);
    }
}

