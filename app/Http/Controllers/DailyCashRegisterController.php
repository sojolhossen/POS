<?php

namespace App\Http\Controllers;

use App\Models\DailyCashRegister;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\StoreCashTransfer;
use App\Services\CashRegisterService;
use App\Services\ExpenseService;
use Exception;
use Illuminate\Http\Request;

class DailyCashRegisterController extends Controller
{
    public function __construct(
        protected CashRegisterService $cashRegisterService,
        protected ExpenseService $expenseService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        // Determine accessible stores
        if ($user->isSuperAdmin()) {
            $accessibleStores = Store::where('is_active', true)->orderBy('is_warehouse')->orderBy('name')->get();
        } elseif ($user->isStoreManager()) {
            $accessibleStores = Store::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id);
                    if ($user->store_id) {
                        $q->orWhere('id', $user->store_id);
                    }
                })
                ->orderBy('name')
                ->get();
        } else {
            $accessibleStores = $user->store_id ? Store::where('id', $user->store_id)->get() : collect();
        }

        // Selected store for current view / actions
        $selectedStoreId = $request->get('store_id');
        if ($selectedStoreId && $accessibleStores->contains('id', $selectedStoreId)) {
            $currentStore = $accessibleStores->firstWhere('id', $selectedStoreId);
        } else {
            $currentStore = $accessibleStores->first() ?? Store::first();
        }

        $query = DailyCashRegister::with(['store', 'opener', 'closer', 'reopener'])->latest('business_date');
        if ($user->isStoreManager() || !$user->isSuperAdmin()) {
            $query->whereIn('store_id', $accessibleStores->pluck('id'));
        }
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $registers = $query->paginate(15, ['*'], 'registers_page');
        $today = now()->format('Y-m-d');

        $todayRegister = null;
        $suggestedOpening = null;
        if ($currentStore) {
            $todayRegister = DailyCashRegister::where('store_id', $currentStore->id)
                ->where('business_date', $today)
                ->first();

            $suggestedOpening = $this->cashRegisterService->getSuggestedOpeningCash($currentStore);
        }

        // Remittance / Cash transfers query
        $transfersQuery = StoreCashTransfer::with(['store', 'transferredBy', 'reviewedBy', 'financialAccount'])->latest();
        if (!$user->isSuperAdmin()) {
            $transfersQuery->whereIn('store_id', $accessibleStores->pluck('id'));
        } elseif ($request->filled('store_id')) {
            $transfersQuery->where('store_id', $request->store_id);
        }
        $transfers = $transfersQuery->paginate(10, ['*'], 'transfers_page');

        $financialAccounts = FinancialAccount::where('is_active', true)->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('daily_registers.index', compact(
            'registers',
            'todayRegister',
            'currentStore',
            'accessibleStores',
            'suggestedOpening',
            'transfers',
            'financialAccounts',
            'expenseCategories'
        ));
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'opening_cash' => 'required|numeric|min:0',
            'opening_discrepancy_reason' => 'nullable|string',
        ]);

        $store = Store::findOrFail($validated['store_id']);

        try {
            $register = $this->cashRegisterService->openRegister(
                $store,
                auth()->user(),
                (float) $validated['opening_cash'],
                $validated['opening_discrepancy_reason'] ?? null
            );

            return redirect()->route('daily-registers.index', ['store_id' => $store->id])
                ->with('success', "Daily Cash Register opened successfully for {$store->name} with ৳" . number_format($register->opening_cash, 2) . ".");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function close(Request $request, DailyCashRegister $register)
    {
        $validated = $request->validate([
            'closing_cash_counted' => 'required|numeric|min:0',
            'discrepancy_reason' => 'nullable|string',
        ]);

        try {
            $this->cashRegisterService->closeRegister(
                $register,
                auth()->user(),
                (float) $validated['closing_cash_counted'],
                $validated['discrepancy_reason'] ?? null
            );

            return redirect()->route('daily-registers.index', ['store_id' => $register->store_id])
                ->with('success', "Daily Cash Register closed and day locked for {$register->store->name}. Counted cash: ৳" . number_format($validated['closing_cash_counted'], 2));
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function reopen(Request $request, DailyCashRegister $register)
    {
        $validated = $request->validate([
            'reopen_reason' => 'required|string|min:5',
        ]);

        try {
            $this->cashRegisterService->reopenRegister(
                $register,
                auth()->user(),
                $validated['reopen_reason']
            );

            return redirect()->route('daily-registers.index', ['store_id' => $register->store_id])
                ->with('success', "Cash Register reopened for {$register->store->name}. Sales may resume.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store manager transfers cash from register to owner / bank account.
     */
    public function transferCash(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
            'transfer_method' => 'required|string|in:cash_to_owner,bank_deposit,bkash,nagad,rocket,other',
            'financial_account_id' => 'nullable|exists:financial_accounts,id',
            'recipient_name' => 'nullable|string|max:150',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $store = Store::findOrFail($validated['store_id']);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('cash_transfers', 'public');
        }

        try {
            $transfer = $this->cashRegisterService->transferCashToOwner(
                store: $store,
                user: auth()->user(),
                amount: (float) $validated['amount'],
                transferMethod: $validated['transfer_method'],
                financialAccountId: $validated['financial_account_id'] ?? null,
                recipientName: $validated['recipient_name'] ?? null,
                referenceNumber: $validated['reference_number'] ?? null,
                notes: $validated['notes'] ?? null,
                attachmentPath: $attachmentPath
            );

            return redirect()->route('daily-registers.index', ['store_id' => $store->id])
                ->with('success', "Cash transfer of ৳" . number_format($transfer->amount, 2) . " submitted ({$transfer->transfer_number}). Drawer balance reduced immediately. Awaiting Super Admin verification.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Super Admin approves cash transfer.
     */
    public function approveTransfer(Request $request, StoreCashTransfer $transfer)
    {
        try {
            $this->cashRegisterService->approveCashTransfer($transfer, auth()->user());

            return redirect()->route('daily-registers.index', ['store_id' => $transfer->store_id])
                ->with('success', "Cash transfer {$transfer->transfer_number} (৳" . number_format($transfer->amount, 2) . ") approved and verified successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Super Admin rejects cash transfer.
     * Cash is immediately refunded back to the store cash drawer.
     */
    public function rejectTransfer(Request $request, StoreCashTransfer $transfer)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:3',
        ]);

        try {
            $this->cashRegisterService->rejectCashTransfer(
                $transfer,
                auth()->user(),
                $validated['rejection_reason']
            );

            return redirect()->route('daily-registers.index', ['store_id' => $transfer->store_id])
                ->with('success', "Cash transfer {$transfer->transfer_number} rejected. ৳" . number_format($transfer->amount, 2) . " has been restored back into the store cash drawer.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record petty cash expense directly from cash drawer.
     */
    public function recordExpense(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $store = Store::findOrFail($validated['store_id']);
        $category = ExpenseCategory::findOrFail($validated['expense_category_id']);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('expense_receipts', 'public');
        }

        try {
            $today = now()->format('Y-m-d');
            $register = DailyCashRegister::where('store_id', $store->id)
                ->where('business_date', $today)
                ->first();

            if (!$register || !$register->isOpen()) {
                throw new Exception("An open daily cash register is required for {$store->name} to record drawer expenses.");
            }

            if ((float) $validated['amount'] > (float) $register->cash_in_hand_expected) {
                throw new Exception("Expense amount (৳" . number_format($validated['amount'], 2) . ") exceeds available drawer cash (৳" . number_format($register->cash_in_hand_expected, 2) . ").");
            }

            $expense = $this->expenseService->createExpense(
                store: $store,
                category: $category,
                amount: (float) $validated['amount'],
                expenseDate: $today,
                title: $validated['title'],
                user: auth()->user(),
                paymentMethod: 'cash',
                description: $validated['description'] ?? null,
                receiptAttachmentPath: $receiptPath,
                autoApproveIfAuthorized: true
            );

            return redirect()->route('daily-registers.index', ['store_id' => $store->id])
                ->with('success', "Drawer expense of ৳" . number_format($expense->amount, 2) . " logged successfully ({$expense->expense_number}). Cash balance updated.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
