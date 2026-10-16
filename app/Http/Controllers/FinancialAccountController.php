<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\FinancialAccountTransaction;
use App\Models\Store;
use App\Services\FinancialAccountService;
use Illuminate\Http\Request;

class FinancialAccountController extends Controller
{
    public function __construct(
        protected FinancialAccountService $accountService
    ) {}

    public function index(Request $request)
    {
        $accounts = FinancialAccount::with('store')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        $stores = Store::where('is_active', true)->get();

        $recentTransactions = FinancialAccountTransaction::with(['account', 'relatedAccount', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('financial_accounts.index', compact('accounts', 'stores', 'recentTransactions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'account_type' => 'required|string|in:cash,bank,bkash,nagad,rocket,card_gateway',
            'account_number' => 'nullable|string|max:80',
            'bank_name' => 'nullable|string|max:100',
            'branch_name' => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:50',
            'store_id' => 'nullable|exists:stores,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $account = FinancialAccount::create([
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'account_number' => $validated['account_number'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'branch_name' => $validated['branch_name'] ?? null,
            'routing_number' => $validated['routing_number'] ?? null,
            'store_id' => $validated['store_id'] ?? null,
            'opening_balance' => $validated['opening_balance'],
            'current_balance' => $validated['opening_balance'],
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        if ($validated['opening_balance'] > 0) {
            FinancialAccountTransaction::create([
                'financial_account_id' => $account->id,
                'transaction_type' => 'deposit',
                'amount' => $validated['opening_balance'],
                'fee_amount' => 0.00,
                'balance_after' => $validated['opening_balance'],
                'description' => 'Opening balance initialization',
                'reconciliation_status' => 'reconciled',
                'user_id' => auth()->id() ?? 1,
            ]);
        }

        return redirect()->route('financial-accounts.index')
            ->with('success', "Financial account {$account->name} created successfully.");
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'source_account_id' => 'required|exists:financial_accounts,id',
            'destination_account_id' => 'required|exists:financial_accounts,id|different:source_account_id',
            'amount' => 'required|numeric|min:1',
            'fee_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $source = FinancialAccount::findOrFail($validated['source_account_id']);
        $dest = FinancialAccount::findOrFail($validated['destination_account_id']);

        try {
            $res = $this->accountService->transfer(
                $source,
                $dest,
                (float) $validated['amount'],
                (float) ($validated['fee_amount'] ?? 0),
                auth()->user(),
                $validated['notes'] ?? null
            );

            return redirect()->route('financial-accounts.index')
                ->with('success', "Transfer of ৳{$validated['amount']} completed successfully (Ref: {$res['transfer_reference']}).");
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function reconcile(FinancialAccountTransaction $transaction)
    {
        $this->accountService->reconcileTransaction($transaction, auth()->user());

        return back()->with('success', "Transaction {$transaction->reference_number} marked as reconciled.");
    }
}
