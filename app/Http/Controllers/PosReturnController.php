<?php

namespace App\Http\Controllers;

use App\Models\PosReturn;
use App\Models\PosSale;
use App\Models\Store;
use App\Services\PosReturnService;
use Exception;
use Illuminate\Http\Request;

class PosReturnController extends Controller
{
    public function __construct(
        protected PosReturnService $returnService
    ) {}

    public function index(Request $request)
    {
        $query = PosReturn::with(['originalSale', 'store', 'processedBy', 'customer'])->latest();

        if (auth()->user()->isStoreManager() && auth()->user()->store_id) {
            $query->where('store_id', auth()->user()->store_id);
        }

        $returns = $query->paginate(15);
        return view('returns.index', compact('returns'));
    }

    public function lookup(Request $request)
    {
        $invoiceNumber = trim($request->input('invoice_number', ''));
        $sale = null;

        if (!empty($invoiceNumber)) {
            $sale = PosSale::with(['store', 'customer', 'items.productVariant.product', 'payments'])
                ->where('invoice_number', $invoiceNumber)
                ->first();
        }

        return view('returns.create', compact('sale', 'invoiceNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pos_sale_id' => 'required|exists:pos_sales,id',
            'return_type' => 'required|string|in:refund,exchange',
            'settlement_method' => 'required|string|in:cash,bkash,nagad,card,exchange',
            'items' => 'required|array|min:1',
            'items.*.pos_sale_item_id' => 'required|exists:pos_sale_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.condition' => 'required|string|in:sellable,damaged,defective',
            'items.*.reason' => 'nullable|string',
            'is_receiptless' => 'boolean',
            'manager_approval_code' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $sale = PosSale::findOrFail($validated['pos_sale_id']);
        $store = auth()->user()->store ?? $sale->store;
        $isReceiptless = (bool) ($validated['is_receiptless'] ?? false);

        // Store manager approval check
        $managerApprover = null;
        if (auth()->user()->isStoreManager() || auth()->user()->isSuperAdmin()) {
            $managerApprover = auth()->user();
        }

        try {
            $posReturn = $this->returnService->processReturn(
                $sale,
                $store,
                auth()->user(),
                $validated['items'],
                $validated['return_type'],
                $validated['settlement_method'],
                $isReceiptless,
                $managerApprover,
                null,
                null,
                $validated['reason'] ?? null
            );

            return redirect()->route('returns.receipt', $posReturn)
                ->with('success', "Return {$posReturn->return_number} processed successfully.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function receipt(PosReturn $posReturn)
    {
        $posReturn->load(['originalSale', 'store', 'customer', 'processedBy', 'approvedBy', 'items.productVariant.product']);
        return view('returns.receipt', compact('posReturn'));
    }
}
