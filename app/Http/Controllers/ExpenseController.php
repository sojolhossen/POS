<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Services\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Expense::with(['store', 'category', 'creator', 'approver', 'financialAccount'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc');

        // Store scoping
        if ($user->isStoreManager() && $user->store_id) {
            $query->where('store_id', $user->store_id);
        } elseif ($request->filled('store_id') && ($user->isSuperAdmin() || $user->isAccountant())) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        $expenses = $query->paginate(15)->withQueryString();
        $stores = Store::where('is_active', true)->get();
        $categories = ExpenseCategory::where('is_active', true)->get();
        $financialAccounts = FinancialAccount::where('is_active', true)->get();

        return view('expenses.index', compact('expenses', 'stores', 'categories', 'financialAccounts'));
    }

    public function create()
    {
        $user = auth()->user();
        $stores = ($user->isStoreManager() && $user->store_id)
            ? Store::where('id', $user->store_id)->get()
            : Store::where('is_active', true)->get();

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $accounts = FinancialAccount::where('is_active', true)->get();

        return view('expenses.create', compact('stores', 'categories', 'accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'title' => 'nullable|string|max:150',
            'payment_method' => 'required|string',
            'financial_account_id' => 'nullable|exists:financial_accounts,id',
            'description' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'auto_approve' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        if ($user->isStoreManager() && $user->store_id && $validated['store_id'] != $user->store_id) {
            abort(403, "You can only submit expenses for your assigned store.");
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'local');
        }

        $store = Store::findOrFail($validated['store_id']);
        $category = ExpenseCategory::findOrFail($validated['expense_category_id']);
        $account = !empty($validated['financial_account_id']) ? FinancialAccount::find($validated['financial_account_id']) : null;
        $title = !empty($validated['title']) ? $validated['title'] : ($validated['description'] ? substr($validated['description'], 0, 50) : $category->name);
        $method = $validated['payment_method'] === 'bank' ? 'bank_transfer' : $validated['payment_method'];

        try {
            $expense = $this->expenseService->createExpense(
                $store,
                $category,
                (float) $validated['amount'],
                $validated['expense_date'],
                $title,
                $user,
                $method,
                $account,
                $validated['description'] ?? null,
                $receiptPath,
                $request->has('auto_approve') ? $request->boolean('auto_approve') : true
            );

            return redirect()->route('expenses.index')
                ->with('success', "Expense {$expense->expense_number} recorded successfully (Status: {$expense->status}).");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function approve(Expense $expense)
    {
        try {
            $this->expenseService->approveExpense($expense, auth()->user());
            return redirect()->route('expenses.index')->with('success', "Expense {$expense->expense_number} approved successfully.");
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Unauthorized') || str_contains($e->getMessage(), 'Self-approval') || str_contains($e->getMessage(), 'not permitted')) {
                abort(403, $e->getMessage());
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        try {
            $this->expenseService->rejectExpense($expense, auth()->user(), $validated['rejection_reason']);
            return back()->with('success', "Expense {$expense->expense_number} has been rejected.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pay(Request $request, Expense $expense)
    {
        $account = $request->filled('financial_account_id')
            ? FinancialAccount::find($request->financial_account_id)
            : null;

        try {
            $this->expenseService->payExpense($expense, auth()->user(), $account);
            return back()->with('success', "Expense {$expense->expense_number} paid successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
