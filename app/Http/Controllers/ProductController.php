<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditService;
use App\Services\BarcodeService;
use App\Services\SkuGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'subcategory', 'variants'])
            ->withCount('variants');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('brand', 'LIKE', "%{$search}%")
                    ->orWhereHas('variants', function ($vq) use ($search) {
                        $vq->where('barcode', 'LIKE', "%{$search}%");
                    });
            });
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::active()->rootOnly()->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::with('subcategories')->active()->rootOnly()->orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created product along with initial size/color variants and barcodes.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:100'],
            'season' => ['nullable', 'string', 'max:100'],
            'fabric' => ['nullable', 'string', 'max:100'],
            'base_purchase_price' => ['required', 'numeric', 'min:0'],
            'base_selling_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            // Variants array: sizes and colors
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'string', 'max:50'],
            'variants.*.color' => ['required', 'string', 'max:50'],
            'variants.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.selling_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $category = Category::findOrFail($validated['category_id']);

        DB::transaction(function () use ($validated, $category, $request) {
            // Auto-generate unique concurrency-safe SKU using category code (e.g. SH-000123)
            $sku = SkuGeneratorService::generate($category);

            $product = Product::create([
                'name' => $validated['name'],
                'sku' => $sku,
                'category_id' => $category->id,
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'season' => $validated['season'] ?? null,
                'fabric' => $validated['fabric'] ?? null,
                'base_purchase_price' => $validated['base_purchase_price'],
                'base_selling_price' => $validated['base_selling_price'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Track initial price history
            PriceHistory::create([
                'product_id' => $product->id,
                'product_variant_id' => null,
                'old_price' => 0.00,
                'new_price' => $product->base_selling_price,
                'reason' => 'Initial catalog product creation',
                'user_id' => Auth::id(),
                'created_at' => now(),
            ]);

            // Create Variants with Unique Code 128 barcodes
            $seenVariants = [];
            foreach ($validated['variants'] as $v) {
                $size = trim($v['size']);
                $color = trim($v['color']);
                $comboKey = strtolower("{$size}-{$color}");

                if (in_array($comboKey, $seenVariants, true)) {
                    continue; // Skip duplicate payload inputs
                }
                $seenVariants[] = $comboKey;

                // Unique Code 128 barcode
                $barcode = BarcodeService::generateUniqueBarcode();

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $size,
                    'color' => $color,
                    'barcode' => $barcode,
                    'purchase_price' => !empty($v['purchase_price']) ? $v['purchase_price'] : null,
                    'selling_price' => !empty($v['selling_price']) ? $v['selling_price'] : null,
                    'is_active' => true,
                ]);

                if ($variant->selling_price !== null) {
                    PriceHistory::create([
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'old_price' => 0.00,
                        'new_price' => $variant->selling_price,
                        'reason' => "Initial variant override ({$color} - {$size})",
                        'user_id' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }

            AuditService::log(
                action: 'product.created',
                auditable: $product,
                newValues: ['sku' => $sku, 'name' => $product->name, 'variants_count' => count($seenVariants)],
                reason: 'Created product with auto-generated SKU and variants'
            );
        });

        return redirect()->route('products.index')->with('success', 'Product created with unique SKU and variants.');
    }

    /**
     * Display product detail with variants and price history.
     */
    public function show(Product $product): View
    {
        $product->load(['category', 'subcategory', 'variants.stockBalances.store', 'priceHistories.user']);

        return view('products.show', compact('product'));
    }

    /**
     * Show form for editing product metadata.
     */
    public function edit(Product $product): View
    {
        $categories = Category::with('subcategories')->active()->rootOnly()->orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update product metadata.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:100'],
            'season' => ['nullable', 'string', 'max:100'],
            'fabric' => ['nullable', 'string', 'max:100'],
            'base_purchase_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $old = $product->only(['name', 'brand', 'season', 'fabric', 'base_purchase_price', 'is_active']);

        $product->update([
            'name' => $validated['name'],
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'season' => $validated['season'] ?? null,
            'fabric' => $validated['fabric'] ?? null,
            'base_purchase_price' => $validated['base_purchase_price'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::log(
            action: 'product.updated',
            auditable: $product,
            oldValues: $old,
            newValues: $product->only(['name', 'brand', 'season', 'fabric', 'base_purchase_price', 'is_active']),
            reason: 'Product metadata updated'
        );

        return redirect()->route('products.show', $product)->with('success', 'Product updated successfully.');
    }

    /**
     * Update Selling Price (Super Admin ONLY).
     * Retains old price, new price, reason, user, and timestamp.
     */
    public function updatePrice(Request $request, Product $product): RedirectResponse
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, __('messages.super_admin_price_only'));
        }

        $validated = $request->validate([
            'new_price' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        DB::transaction(function () use ($validated, $product) {
            if (!empty($validated['product_variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)
                    ->findOrFail($validated['product_variant_id']);

                $oldPrice = $variant->effective_selling_price;
                $variant->selling_price = $validated['new_price'];
                $variant->save();

                PriceHistory::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'old_price' => $oldPrice,
                    'new_price' => $validated['new_price'],
                    'reason' => $validated['reason'],
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ]);

                AuditService::log(
                    action: 'price.variant_updated',
                    auditable: $variant,
                    oldValues: ['selling_price' => $oldPrice],
                    newValues: ['selling_price' => $validated['new_price']],
                    reason: $validated['reason']
                );
            } else {
                $oldPrice = $product->base_selling_price;
                $product->base_selling_price = $validated['new_price'];
                $product->save();

                PriceHistory::create([
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'old_price' => $oldPrice,
                    'new_price' => $validated['new_price'],
                    'reason' => $validated['reason'],
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ]);

                AuditService::log(
                    action: 'price.product_updated',
                    auditable: $product,
                    oldValues: ['base_selling_price' => $oldPrice],
                    newValues: ['base_selling_price' => $validated['new_price']],
                    reason: $validated['reason']
                );
            }
        });

        return redirect()->route('products.show', $product)->with('success', 'Selling price updated and logged in price history.');
    }

    /**
     * Add a new variant to an existing product.
     */
    public function addVariant(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Prevent duplicates
        $exists = ProductVariant::where('product_id', $product->id)
            ->where('size', $validated['size'])
            ->where('color', $validated['color'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['size' => 'A variant with this size and color already exists for this product.']);
        }

        $barcode = BarcodeService::generateUniqueBarcode();

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => $validated['size'],
            'color' => $validated['color'],
            'barcode' => $barcode,
            'purchase_price' => $validated['purchase_price'] ?? null,
            'selling_price' => $validated['selling_price'] ?? null,
            'is_active' => true,
        ]);

        AuditService::log(
            action: 'variant.created',
            auditable: $variant,
            newValues: ['size' => $variant->size, 'color' => $variant->color, 'barcode' => $barcode],
            reason: 'Added new variant to product'
        );

        return redirect()->route('products.show', $product)->with('success', 'New variant created successfully.');
    }
}
