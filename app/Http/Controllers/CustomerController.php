<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LoyaltyPointLedger;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('sales')->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }

        $customers = $query->paginate(15);
        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales' => fn ($q) => $q->latest()->take(10), 'pointLedgers' => fn ($q) => $q->latest()->take(20)]);
        return view('customers.show', compact('customer'));
    }
}
