@extends('layouts.app')

@section('title', 'Controlled Data Import & Migration')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Controlled Data Import Workflows</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Secure batch import for products, opening inventory, suppliers, customers, and employees with row validation and duplicate prevention.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900/50 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50 dark:text-rose-200">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Upload Card -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-6">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Upload Import Dataset (CSV Format)</h2>

        <form action="{{ route('imports.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Dataset Entity Type *</label>
                    <select name="type" id="type" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm text-slate-900 dark:text-white">
                        <option value="products">Products & Variants</option>
                        <option value="opening_stock">Opening Inventory Ledger (WAC Cost & Qty)</option>
                        <option value="suppliers">Suppliers & Payables</option>
                        <option value="customers">Customers & Loyalty Phone Numbers</option>
                        <option value="employees">Employees & Base Salaries</option>
                    </select>
                </div>

                <div>
                    <label for="file" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Select CSV File (Max 10MB) *</label>
                    <input type="file" name="file" id="file" accept=".csv,text/csv" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950/50 dark:file:text-emerald-300">
                </div>
            </div>

            <!-- Download Sample CSV Template Links -->
            <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 space-y-2">
                <span class="font-bold text-slate-900 dark:text-white">Download Standard CSV Templates:</span>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('imports.template', 'products') }}" class="text-emerald-600 hover:underline">📥 Products Template</a>
                    <a href="{{ route('imports.template', 'opening_stock') }}" class="text-emerald-600 hover:underline">📥 Opening Stock Template</a>
                    <a href="{{ route('imports.template', 'suppliers') }}" class="text-emerald-600 hover:underline">📥 Suppliers Template</a>
                    <a href="{{ route('imports.template', 'customers') }}" class="text-emerald-600 hover:underline">📥 Customers Template</a>
                    <a href="{{ route('imports.template', 'employees') }}" class="text-emerald-600 hover:underline">📥 Employees Template</a>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-lg shadow-sm">
                    Upload & Validate Preview &rarr;
                </button>
            </div>
        </form>
    </div>

    <!-- Import Audit Trail / History Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Import History & Audit Log</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3">Initiated At</th>
                        <th class="px-6 py-3">Entity Type</th>
                        <th class="px-6 py-3">File Name</th>
                        <th class="px-6 py-3 text-right">Total Rows</th>
                        <th class="px-6 py-3 text-right">Valid Rows</th>
                        <th class="px-6 py-3 text-right">Error Rows</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3">Actor</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($imports as $imp)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                {{ \Carbon\Carbon::parse($imp->created_at)->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white capitalize">
                                {{ str_replace('_', ' ', $imp->type) }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-600 dark:text-slate-300">
                                {{ $imp->file_name }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono">{{ $imp->total_rows }}</td>
                            <td class="px-6 py-4 text-right font-mono text-emerald-600 font-bold">{{ $imp->valid_rows }}</td>
                            <td class="px-6 py-4 text-right font-mono text-rose-600 font-bold">{{ $imp->error_rows }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    {{ $imp->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' :
                                       ($imp->status === 'validated' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700') }}">
                                    {{ ucfirst($imp->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $imp->user->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-right text-xs">
                                @if($imp->status === 'validated')
                                    <a href="{{ route('imports.preview', $imp) }}" class="text-emerald-600 font-semibold hover:text-emerald-700">Preview & Confirm &rarr;</a>
                                @else
                                    <a href="{{ route('imports.preview', $imp) }}" class="text-slate-500 hover:text-slate-700">View Log</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                No data import runs on record.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($imports->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $imports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
