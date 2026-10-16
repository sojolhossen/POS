<?php

namespace App\Http\Controllers;

use App\Models\DailyCashRegister;
use App\Models\Store;
use App\Services\CashRegisterService;
use Exception;
use Illuminate\Http\Request;

class DailyCashRegisterController extends Controller
{
    public function __construct(
        protected CashRegisterService $cashRegisterService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = DailyCashRegister::with(['store', 'opener', 'closer', 'reopener'])->latest('business_date');

        if ($user->isStoreManager() && $user->store_id) {
            $query->where('store_id', $user->store_id);
        }

        $registers = $query->paginate(15);
        $today = now()->format('Y-m-d');
        $currentStore = $user->store ?? Store::first();

        $todayRegister = null;
        if ($currentStore) {
            $todayRegister = DailyCashRegister::where('store_id', $currentStore->id)
                ->where('business_date', $today)
                ->first();
        }

        return view('daily_registers.index', compact('registers', 'todayRegister', 'currentStore'));
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $store = Store::findOrFail($validated['store_id']);

        try {
            $register = $this->cashRegisterService->openRegister(
                $store,
                auth()->user(),
                (float) $validated['opening_cash']
            );

            return redirect()->route('daily-registers.index')->with('success', "Daily Cash Register opened successfully for {$store->name} with ৳{$register->opening_cash}.");
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

            return redirect()->route('daily-registers.index')->with('success', "Daily Cash Register closed and day locked for {$register->store->name}.");
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

            return redirect()->route('daily-registers.index')->with('success', "Cash Register reopened for {$register->store->name}. Sales may resume.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
