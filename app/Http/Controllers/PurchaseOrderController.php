<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\PurchaseService;
use Exception;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService,
        protected AuditService $auditService
    ) {}

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'destinationStore', 'creator'])->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $purchaseOrders = $query->paginate(15);
        $suppliers = Supplier::where('is_active', true)->get();

        return view('purchase_orders.index', compact('purchaseOrders', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouse = Store::warehouseOnly()->first() ?? Store::first();
        $variants = ProductVariant::with('product')->where('is_active', true)->get();

        return view('purchase_orders.create', compact('suppliers', 'warehouse', 'variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'destination_store_id' => 'required|exists:stores,id',
            'additional_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $store = Store::findOrFail($validated['destination_store_id']);
            $additionalCosts = (float) ($validated['additional_costs'] ?? 0.0);

            $po = $this->purchaseService->createPurchaseOrder(
                $supplier,
                $store,
                $validated['items'],
                $additionalCosts,
                auth()->user(),
                $validated['notes'] ?? null
            );

            return redirect()->route('purchase-orders.show', $po)->with('success', "Purchase Order {$po->po_number} submitted successfully.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'destinationStore', 'items.productVariant.product', 'receivings.items.productVariant.product', 'creator', 'approver']);
        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->purchaseService->approvePurchaseOrder($purchaseOrder, auth()->user());
            return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', "Purchase Order {$purchaseOrder->po_number} approved successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (in_array($purchaseOrder->status, ['partially_received', 'completed'])) {
            return back()->with('error', 'Cannot cancel an order that has already received stock.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        AuditService::log(
            action: 'purchase_order.cancelled',
            auditable: $purchaseOrder,
            oldValues: null,
            newValues: ['po_number' => $purchaseOrder->po_number],
            reason: "Cancelled Purchase Order {$purchaseOrder->po_number}"
        );

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', "Purchase Order {$purchaseOrder->po_number} cancelled.");
    }
}
