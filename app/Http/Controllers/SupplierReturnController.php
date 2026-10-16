<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Services\SupplierReturnService;
use Exception;
use Illuminate\Http\Request;

class SupplierReturnController extends Controller
{
    public function __construct(
        protected SupplierReturnService $supplierReturnService
    ) {}

    public function index(Request $request)
    {
        $query = SupplierReturn::with(['supplier', 'store', 'creator'])->latest();
        $returns = $query->paginate(15);
        return view('supplier_returns.index', compact('returns'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $stores = Store::active()->get();
        $variants = ProductVariant::with('product')->where('is_active', true)->get();

        return view('supplier_returns.create', compact('suppliers', 'stores', 'variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'store_id' => 'required|exists:stores,id',
            'settlement_type' => 'required|string|in:credit_note,cash_refund,bank_refund',
            'settlement_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.condition' => 'required|string|in:damaged,excess',
            'items.*.reason' => 'nullable|string',
        ]);

        try {
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $store = Store::findOrFail($validated['store_id']);

            $supplierReturn = $this->supplierReturnService->processSupplierReturn(
                $supplier,
                $store,
                $validated['items'],
                $validated['settlement_type'],
                auth()->user(),
                $validated['settlement_reference'] ?? null,
                $validated['notes'] ?? null
            );

            return redirect()->route('supplier-returns.show', $supplierReturn)
                ->with('success', "Supplier return {$supplierReturn->return_number} processed successfully.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(SupplierReturn $supplierReturn)
    {
        $supplierReturn->load(['supplier', 'store', 'items.productVariant.product', 'creator']);
        return view('supplier_returns.show', compact('supplierReturn'));
    }
}
