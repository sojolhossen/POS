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
        $query = StockTransfer::with(['sourceStore', 'destinationStore', 'creator', 'approver', 'dispatcher', 'receiver', 'items.productVariant'])->latest();

        // Enforce store scoping if user is store manager or cashier
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
        $transfer->load([
            'sourceStore',
            'destinationStore',
            'items.productVariant.product',
            'creator',
            'approver',
            'dispatcher',
            'receiver',
            'auditLogs.user'
        ]);
        return view('transfers.show', compact('transfer'));
    }

    public function printChallan(Request $request, StockTransfer $transfer)
    {
        $transfer->load([
            'sourceStore',
            'destinationStore',
            'items.productVariant.product',
            'creator',
            'approver',
            'dispatcher',
            'receiver',
            'auditLogs.user'
        ]);

        $copy = $request->query('copy');
        if (!$copy || $copy === 'auto') {
            $userRole = auth()->user()?->role instanceof \App\Enums\UserRole
                ? auth()->user()->role->value
                : (string) (auth()->user()?->role ?? 'store_manager');
            $copy = $userRole;
        }

        return view('transfers.print', compact('transfer', 'copy'));
    }

    public function approve(StockTransfer $transfer)
    {
        // STRICT: Only Super Admin can approve stock transfers
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized! Only Super Admin can approve stock transfers.');
        }

        try {
            $this->transferService->approveTransfer($transfer, auth()->user());
            return redirect()->route('transfers.show', $transfer)->with('success', "Transfer {$transfer->reference_number} approved by Super Admin. Stock is authorized for dispatch.");
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

        $user = auth()->user();
        // Any user belonging to destination store (or Super Admin) can receive
        if (!$user->isSuperAdmin() && $user->store_id && $user->store_id != $transfer->destination_store_id) {
            return redirect()->route('transfers.show', $transfer)->with('error', 'Only staff/users of destination store (' . $transfer->destinationStore->name . ') can receive this transfer.');
        }

        $transfer->load(['sourceStore', 'destinationStore', 'items.productVariant.product']);
        return view('transfers.receive', compact('transfer'));
    }

    public function receiveStore(Request $request, StockTransfer $transfer)
    {
        $user = auth()->user();
        // Any user belonging to destination store (or Super Admin) can receive
        if (!$user->isSuperAdmin() && $user->store_id && $user->store_id != $transfer->destination_store_id) {
            return redirect()->route('transfers.show', $transfer)->with('error', 'Only staff/users of destination store (' . $transfer->destinationStore->name . ') can receive this transfer.');
        }

        $validated = $request->validate([
            'received_quantities' => 'required|array',
            'damaged_quantities' => 'nullable|array',
            'discrepancy_reasons' => 'nullable|array',
        ]);

        try {
            $this->transferService->receiveTransfer(
                $transfer,
                $validated['received_quantities'],
                $user,
                $validated['damaged_quantities'] ?? [],
                $validated['discrepancy_reasons'] ?? []
            );

            return redirect()->route('transfers.show', $transfer)->with('success', "Transfer {$transfer->reference_number} confirmed & received by {$user->name}. Destination inventory updated.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
