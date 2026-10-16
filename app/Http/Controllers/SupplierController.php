<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(Request $request)
    {
        $query = Supplier::query()->withCount('purchaseOrders');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()->paginate(15);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Duplicate warning check
        $duplicate = Supplier::where('name', $validated['name'])
            ->orWhere('phone', $validated['phone'])
            ->first();

        if ($duplicate && !$request->has('confirm_duplicate')) {
            return back()->withInput()->with('warning_duplicate', "Warning: A supplier with similar name or phone number already exists: {$duplicate->name} ({$duplicate->phone}). Please confirm if you wish to proceed.");
        }

        $supplier = Supplier::create($validated);

        AuditService::log(
            action: 'supplier.created',
            auditable: $supplier,
            oldValues: null,
            newValues: $validated,
            reason: "Created supplier {$supplier->name}"
        );

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchaseOrders' => fn ($q) => $q->latest()->take(10), 'payments' => fn ($q) => $q->latest()->take(10)]);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $supplier->only(array_keys($validated));
        $supplier->update($validated);

        AuditService::log(
            action: 'supplier.updated',
            auditable: $supplier,
            oldValues: $oldValues,
            newValues: $validated,
            reason: "Updated supplier details for {$supplier->name}"
        );

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated successfully.');
    }

    public function recordPayment(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,cheque,mobile_banking',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($supplier, $validated) {
            $paymentNumber = SequenceService::generate('PAY', 'CENTRAL');

            SupplierPayment::create([
                'payment_number' => $paymentNumber,
                'supplier_id' => $supplier->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Reduce outstanding liability balance
            $supplier->decrement('outstanding_balance', $validated['amount']);

            AuditService::log(
                action: 'supplier.payment_recorded',
                auditable: $supplier,
                oldValues: null,
                newValues: ['payment_number' => $paymentNumber, 'amount' => $validated['amount']],
                reason: "Recorded payment of ৳{$validated['amount']} to {$supplier->name}"
            );
        });

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier payment recorded successfully.');
    }
}
