<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Services\PurchaseService;
use Exception;
use Illuminate\Http\Request;

class PurchaseReceivingController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function create(PurchaseOrder $purchaseOrder)
    {
        if (!$purchaseOrder->isApproved()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', "Purchase Order {$purchaseOrder->po_number} must be approved by the Super Admin before receiving stock.");
        }

        if ($purchaseOrder->status === 'completed') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', "Purchase Order {$purchaseOrder->po_number} is already fully received.");
        }

        $purchaseOrder->load(['supplier', 'destinationStore', 'items.productVariant.product']);
        return view('purchase_receivings.create', compact('purchaseOrder'));
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'supplier_bill_number' => 'nullable|string|max:100',
            'actual_additional_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.quantity_accepted' => 'required|integer|min:0',
            'items.*.quantity_damaged' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.scanned_barcode' => 'nullable|string',
        ]);

        try {
            $receiving = $this->purchaseService->receiveStock(
                $purchaseOrder,
                $purchaseOrder->destinationStore,
                $validated['items'],
                (float) ($validated['actual_additional_costs'] ?? 0.0),
                auth()->user(),
                $validated['supplier_bill_number'] ?? null,
                $validated['notes'] ?? null
            );

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', "Stock received successfully under Receiving #{$receiving->receiving_number}. WAC and stock balances updated.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
