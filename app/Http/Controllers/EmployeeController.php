<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Employee::with('store')->orderBy('employment_status')->orderBy('name');

        if ($user->isStoreManager() && $user->store_id) {
            $query->where('store_id', $user->store_id);
        } elseif ($request->filled('store_id') && ($user->isSuperAdmin() || $user->isAccountant())) {
            $query->where('store_id', $request->store_id);
        }

        $employees = $query->paginate(15);
        $stores = Store::where('is_active', true)->get();

        return view('employees.index', compact('employees', 'stores'));
    }

    public function create()
    {
        $user = auth()->user();
        $stores = ($user->isStoreManager() && $user->store_id)
            ? Store::where('id', $user->store_id)->get()
            : Store::where('is_active', true)->get();

        $users = User::where('is_active', true)->get();

        return view('employees.create', compact('stores', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'required|string|max:50|unique:employees,employee_code',
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'designation' => 'required|string|max:100',
            'store_id' => 'nullable|exists:stores,id',
            'user_id' => 'nullable|exists:users,id',
            'joining_date' => 'required|date',
            'monthly_salary' => 'required|numeric|min:0',
        ]);

        $employee = Employee::create([
            'employee_code' => $validated['employee_code'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'designation' => $validated['designation'],
            'store_id' => $validated['store_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'joining_date' => $validated['joining_date'],
            'monthly_salary' => $validated['monthly_salary'],
            'current_advance_balance' => 0.00,
            'employment_status' => 'active',
        ]);

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->name} ({$employee->employee_code}) added successfully.");
    }

    public function show(Employee $employee)
    {
        $user = auth()->user();
        if ($user->isStoreManager() && $user->store_id && $employee->store_id != $user->store_id) {
            abort(403, "Access restricted to employees in your store.");
        }

        $employee->load(['store', 'attendances' => fn ($q) => $q->orderBy('date', 'desc')->limit(30), 'salaryPayments' => fn ($q) => $q->orderBy('salary_month', 'desc')]);

        return view('employees.show', compact('employee'));
    }
}
