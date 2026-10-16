@extends('layouts.app')

@section('title', __('expenses.create_expense') ?? 'Create Expense')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Record New Expense</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Log an operational expense with mandatory category and store scope.</p>
        </div>
        <a href="{{ route('expenses.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
            &larr; Back to Expenses
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50 dark:text-rose-200">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="store_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Store Scope *</label>
                <select name="store_id" id="store_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ old('store_id', auth()->user()->store_id) == $store->id ? 'selected' : '' }}>
                            {{ $store->name }} ({{ $store->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="expense_category_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Expense Category *</label>
                <select name="expense_category_id" id="expense_category_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }} ({{ $cat->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="amount" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (BDT ৳) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount') }}" required placeholder="0.00" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500 font-mono font-medium">
            </div>

            <div>
                <label for="expense_date" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Expense Date *</label>
                <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="payment_method" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method *</label>
                <select name="payment_method" id="payment_method" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash Drawer</option>
                    <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>Bank Account</option>
                    <option value="bkash" {{ old('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                    <option value="nagad" {{ old('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                    <option value="rocket" {{ old('payment_method') === 'rocket' ? 'selected' : '' }}>Rocket</option>
                    <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Card Settlement</option>
                </select>
            </div>

            <div>
                <label for="financial_account_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Financial Account (Optional for Cash)</label>
                <select name="financial_account_id" id="financial_account_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">None / Cash Register Drawer</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ old('financial_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->account_name }} ({{ strtoupper($acc->account_type) }} - ৳{{ number_format($acc->current_balance, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Description & Business Justification *</label>
            <textarea name="description" id="description" rows="3" required placeholder="Provide purpose, invoice/voucher reference, or justification..." class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-emerald-500 focus:border-emerald-500">{{ old('description') }}</textarea>
        </div>

        <div>
            <label for="receipt" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Receipt / Voucher Attachment (PDF, JPG, PNG - Max 5MB)</label>
            <input type="file" name="receipt" id="receipt" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950/50 dark:file:text-emerald-300">
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('expenses.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Cancel</a>
            <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm focus:ring-2 focus:ring-emerald-500">Record Expense</button>
        </div>
    </form>
</div>
@endsection
