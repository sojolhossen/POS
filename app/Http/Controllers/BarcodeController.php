<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeController extends Controller
{
    /**
     * Display the barcode label printing and scanner workstation.
     */
    public function index(Request $request): View
    {
        $stores = Store::active()->orderBy('name')->get();
        $selectedStore = $request->filled('store_id')
            ? Store::find($request->store_id)
            : ($request->user()->store ?? $stores->first());

        $variants = ProductVariant::with(['product.category'])
            ->active()
            ->latest()
            ->paginate(15);

        return view('barcodes.index', compact('stores', 'selectedStore', 'variants'));
    }

    /**
     * Render printable label sheets with configurable dimensions and store name.
     * Note: Reprinting ALWAYS retains the original barcode.
     */
    public function printLabels(Request $request): View
    {
        $validated = $request->validate([
            'variant_ids' => ['required', 'array', 'min:1'],
            'variant_ids.*' => ['exists:product_variants,id'],
            'quantities' => ['required', 'array'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'label_width' => ['nullable', 'numeric', 'min:30', 'max:100'],  // mm
            'label_height' => ['nullable', 'numeric', 'min:20', 'max:80'], // mm
            'show_store_name' => ['nullable', 'boolean'],
        ]);

        $store = !empty($validated['store_id']) ? Store::find($validated['store_id']) : null;
        $labelWidth = $validated['label_width'] ?? 50;  // Default 50mm
        $labelHeight = $validated['label_height'] ?? 30; // Default 30mm
        $showStoreName = $request->boolean('show_store_name', true);

        $labels = [];
        foreach ($validated['variant_ids'] as $variantId) {
            $variant = ProductVariant::with('product')->findOrFail($variantId);
            $qty = max(1, (int) ($validated['quantities'][$variantId] ?? 1));

            // Generate SVG for existing barcode
            $barcodeSvg = BarcodeService::renderSvg($variant->barcode);

            for ($i = 0; $i < $qty; $i++) {
                $labels[] = [
                    'variant' => $variant,
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
     * API lookup for camera or hardware scanner: resolves scanned barcode to variant data.
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
        $targetStoreId = $user->isSuperAdmin() ? $request->input('store_id') : $user->store_id;

        $storeBalance = null;
        if ($targetStoreId) {
            $storeBalance = $variant->stockBalances->firstWhere('store_id', (int) $targetStoreId);
        }

        return response()->json([
            'success' => true,
            'variant' => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'sku' => $variant->product->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'barcode' => $variant->barcode,
                'selling_price' => $variant->effective_selling_price,
                'selling_price_formatted' => '৳ ' . number_format($variant->effective_selling_price, 2),
                'stock_on_hand' => $storeBalance ? $storeBalance->quantity_on_hand : 0,
                'available_stock' => $storeBalance ? $storeBalance->available_quantity : 0,
            ],
        ]);
    }
}
