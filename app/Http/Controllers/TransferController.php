<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Services\TransferService;
use Exception;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    public function index(Request $request)
    {
        $query = StockTransfer::with(['sourceStore', 'destinationStore', 'creator', 'items.productVariant'])->latest();

        // Enforce store scoping if user is store manager
        if (auth()->user()->isStoreManager() && auth()->user()->store_id) {
            $userStoreId = auth()->user()->store_id;
            $query->where(function ($q) use ($userStoreId) {
                $q->where('source_store_id', $userStoreId)
                  ->orWhere('destination_store_id', $userStoreId);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $transfers = $query->paginate(15);
        $stores = Store::active()->get();

        return view('transfers.index', compact('transfers', 'stores'));
    }

    public function create()
    {
        $stores = Store::active()->get();
        $variants = ProductVariant::with('product')->where('is_active', true)->get();
        return view('transfers.create', compact('stores', 'variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_store_id' => 'required|exists:stores,id',
            'destination_store_id' => 'required|exists:stores,id|different:source_store_id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $source = Store::findOrFail($validated['source_store_id']);
            $dest = Store::findOrFail($validated['destination_store_id']);

            $transfer = $this->transferService->requestTransfer(
                $source,
                $dest,
                $validated['items'],
                auth()->user(),
                $validated['notes'] ?? null
            );

            return redirect()->route('transfers.show', $transfer)
                ->with('success', "Transfer request {$transfer->reference_number} created successfully.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(StockTransfer $transfer)
    {
        $transfer->load(['sourceStore', 'destinationStore', 'items.productVariant.product', 'creator', 'approver', 'dispatcher', 'receiver']);
        return view('transfers.show', compact('transfer'));
    }

    public function approve(StockTransfer $transfer)
    {
        try {
            $this->transferService->approveTransfer($transfer, auth()->user());
            return redirect()->route('transfers.show', $transfer)->with('success', "Transfer {$transfer->reference_number} approved for dispatch.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dispatchView(StockTransfer $transfer)
    {
        if (!in_array($transfer->status, ['approved', 'requested'])) {
            return redirect()->route('transfers.show', $transfer)->with('error', 'Transfer is not ready for dispatch.');
        }

        $transfer->load(['sourceStore', 'destinationStore', 'items.productVariant.product']);
        return view('transfers.dispatch', compact('transfer'));
    }

    public function dispatchStore(Request $request, StockTransfer $transfer)
    {
        $validated = $request->validate([
            'quantities' => 'required|array',
            'quantities.*' => 'required|integer|min:0',
        ]);

        try {
            $this->transferService->dispatchTransfer($transfer, $validated['quantities'], auth()->user());
            return redirect()->route('transfers.show', $transfer)->with('success', "Transfer {$transfer->reference_number} dispatched. Source stock reduced and marked in-transit.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function receiveView(StockTransfer $transfer)
    {
        if ($transfer->status !== 'in_transit') {
            return redirect()->route('transfers.show', $transfer)->with('error', 'Transfer is not currently in transit.');
        }

        $transfer->load(['sourceStore', 'destinationStore', 'items.productVariant.product']);
        return view('transfers.receive', compact('transfer'));
    }

    public function receiveStore(Request $request, StockTransfer $transfer)
    {
        $validated = $request->validate([
            'received_quantities' => 'required|array',
            'damaged_quantities' => 'nullable|array',
            'discrepancy_reasons' => 'nullable|array',
        ]);

        try {
            $this->transferService->receiveTransfer(
                $transfer,
                $validated['received_quantities'],
                auth()->user(),
                $validated['damaged_quantities'] ?? [],
                $validated['discrepancy_reasons'] ?? []
            );

            return redirect()->route('transfers.show', $transfer)->with('success', "Transfer {$transfer->reference_number} confirmed. Destination stock updated.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
