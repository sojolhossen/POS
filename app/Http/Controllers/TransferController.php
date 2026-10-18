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

    public function create(Request $request)
    {
        $user = $request->user();

        // 1. Supplying Location (Source):
        // As requested: "Source Store (Supplying Location) * a shudhu wearhous show korbe"
        $sourceStores = Store::active()->warehouseOnly()->get();
        if ($sourceStores->isEmpty()) {
            $sourceStores = Store::active()->orderBy('is_warehouse', 'desc')->orderBy('name')->get();
        }

        // 2. Receiving Location (Destination):
        // As requested: "Destination Store (Receiving Location) * shudu take as sing kora store gula dekhabe"
        if ($user->isStoreManager()) {
            $destinationStores = Store::active()->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id);
                if ($user->store_id) {
                    $q->orWhere('id', $user->store_id);
                }
            })->get();
        } elseif ($user->isSuperAdmin()) {
            $destinationStores = Store::active()->retailOnly()->get();
            if ($destinationStores->isEmpty()) {
                $destinationStores = Store::active()->get();
            }
        } elseif ($user->store_id) {
            $destinationStores = Store::active()->where('id', $user->store_id)->get();
        } else {
            $destinationStores = Store::active()->get();
        }

        $stores = $destinationStores; // backward compatibility
        $variants = ProductVariant::with('product')->where('is_active', true)->get();

        return view('transfers.create', compact('sourceStores', 'destinationStores', 'stores', 'variants'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'source_store_id' => 'required|exists:stores,id',
            'destination_store_id' => 'required|exists:stores,id|different:source_store_id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Enforce store manager receiving location scoping
        if ($user->isStoreManager() && !$user->canAccessStore((int) $validated['destination_store_id'])) {
            return back()->withInput()->with('error', 'Unauthorized! You can only request stock for stores assigned to you.');
        }

        try {
            $source = Store::findOrFail($validated['source_store_id']);
            $dest = Store::findOrFail($validated['destination_store_id']);

            $transfer = $this->transferService->requestTransfer(
                $source,
                $dest,
                $validated['items'],
                $user,
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

        // Source Store current live stock for each requested variant
        $sourceStocks = \App\Models\StockBalance::where('store_id', $transfer->source_store_id)
            ->whereIn('product_variant_id', $transfer->items->pluck('product_variant_id'))
            ->get()
            ->keyBy('product_variant_id');

        // All active stores for Super Admin in case Supplying Location needs to be changed
        $allStores = auth()->user()?->isSuperAdmin()
            ? Store::active()->orderBy('is_warehouse', 'desc')->orderBy('name')->get()
            : collect();

        // Check if there is any stock shortage at the source store
        $hasStockDeficit = false;
        foreach ($transfer->items as $item) {
            $stock = $sourceStocks[$item->product_variant_id] ?? null;
            $avail = $stock ? $stock->available_quantity : 0;
            if ($avail < $item->quantity_requested) {
                $hasStockDeficit = true;
                break;
            }
        }

        return view('transfers.show', compact('transfer', 'sourceStocks', 'allStores', 'hasStockDeficit'));
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

    public function approve(Request $request, StockTransfer $transfer)
    {
        // STRICT: Only Super Admin can approve stock transfers
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized! Only Super Admin can approve stock transfers.');
        }

        $validated = $request->validate([
            'source_store_id' => 'nullable|exists:stores,id',
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|integer|min:1',
        ]);

        try {
            $newSourceId = !empty($validated['source_store_id']) ? (int) $validated['source_store_id'] : null;
            $updatedQuantities = $validated['quantities'] ?? [];

            $this->transferService->approveTransfer($transfer, auth()->user(), $newSourceId, $updatedQuantities);

            return redirect()->route('transfers.show', $transfer)
                ->with('success', "Transfer {$transfer->reference_number} approved by Super Admin. Stock is authorized for dispatch.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateSourceAndItems(Request $request, StockTransfer $transfer)
    {
        // STRICT: Only Super Admin can modify transfer source and quantities
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized! Only Super Admin can modify supplying source and quantities.');
        }

        $validated = $request->validate([
            'source_store_id' => 'required|exists:stores,id',
            'quantities' => 'required|array',
            'quantities.*' => 'required|integer|min:1',
        ]);

        if ((int) $validated['source_store_id'] === (int) $transfer->destination_store_id) {
            return back()->with('error', 'Supplying location cannot be the same as receiving location.');
        }

        try {
            $this->transferService->modifyTransferSourceAndItems(
                $transfer,
                auth()->user(),
                (int) $validated['source_store_id'],
                $validated['quantities']
            );

            return redirect()->route('transfers.show', $transfer)
                ->with('success', "Supplying source and requested quantities updated successfully by Super Admin.");
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
