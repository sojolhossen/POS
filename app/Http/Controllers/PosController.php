<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DailyCashRegister;
use App\Models\PosSale;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\StockBalance;
use App\Models\Store;
use App\Services\PosCheckoutService;
use Exception;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected PosCheckoutService $checkoutService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $store = $user->store ?? Store::first();

        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'No retail store found for POS operations.');
        }

        // Check if daily cash register is opened for today
        $today = now()->format('Y-m-d');
        $register = DailyCashRegister::where('store_id', $store->id)
            ->where('business_date', $today)
            ->first();

        $activePromotions = Promotion::active()->with(['product', 'category'])->get();
        $recentSales = PosSale::where('store_id', $store->id)->latest()->take(5)->get();

        return view('pos.index', compact('store', 'register', 'activePromotions', 'recentSales'));
    }

    /**
     * Fast search endpoint for barcode scanner and autocomplete.
     */
    public function searchProduct(Request $request)
    {
        $query = trim($request->input('q', ''));
        if (empty($query)) {
            return response()->json([]);
        }

        $user = auth()->user();
        $storeId = $user->store_id ?? Store::first()->id;

        $variants = ProductVariant::with(['product.category'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('barcode', $query)
                  ->orWhere('barcode', 'like', "%{$query}%")
                  ->orWhereHas('product', function ($pq) use ($query) {
                      $pq->where('sku', 'like', "%{$query}%")
                         ->orWhere('name', 'like', "%{$query}%");
                  });
            })
            ->take(15)
            ->get();

        $results = [];
        foreach ($variants as $v) {
            $balance = StockBalance::where('store_id', $storeId)
                ->where('product_variant_id', $v->id)
                ->first();

            $results[] = [
                'id' => $v->id,
                'barcode' => $v->barcode,
                'sku' => $v->product->sku,
                'name' => $v->product->name,
                'size' => $v->size,
                'color' => $v->color,
                'product_id' => $v->product_id,
                'category_id' => $v->product->category_id,
                'price' => (float) $v->effective_selling_price,
                'available_stock' => $balance ? $balance->available_quantity : 0,
            ];
        }

        return response()->json($results);
    }

    /**
     * Fast search endpoint for customer lookup by phone or name.
     */
    public function searchCustomer(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->where('phone', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'name', 'phone', 'loyalty_points', 'total_spend']);

        return response()->json($customers);
    }

    /**
     * Process checkout form submission.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|string|in:cash,bkash,nagad,rocket,card',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.tendered_amount' => 'nullable|numeric|min:0',
            'payments.*.card_last_four' => 'nullable|string|max:4',
            'payments.*.reference' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:120',
            'promotion_code' => 'nullable|string',
            'redeem_points' => 'nullable|integer|min:0',
            'idempotency_key' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $store = $user->store ?? Store::first();

        // Customer lookup or creation if phone provided
        $customer = null;
        if (!empty($validated['customer_phone'])) {
            $customer = Customer::firstOrCreate(
                ['phone' => $validated['customer_phone']],
                ['name' => $validated['customer_name'] ?? 'Walk-in Customer']
            );
            if (!empty($validated['customer_name']) && $customer->name !== $validated['customer_name']) {
                $customer->update(['name' => $validated['customer_name']]);
            }
        }

        try {
            $sale = $this->checkoutService->checkout(
                $store,
                $user,
                $validated['items'],
                $validated['payments'],
                $customer,
                $validated['promotion_code'] ?? null,
                (int) ($validated['redeem_points'] ?? 0),
                $validated['idempotency_key'] ?? null,
                $validated['notes'] ?? null
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'invoice_number' => $sale->invoice_number,
                    'receipt_url' => route('pos.receipt', $sale),
                ]);
            }

            return redirect()->route('pos.receipt', $sale)->with('success', "Sale {$sale->invoice_number} completed successfully.");
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * 80mm printable thermal receipt.
     */
    public function receipt(PosSale $sale)
    {
        $sale->load(['store', 'cashier', 'customer', 'promotion', 'items', 'payments']);
        return view('pos.receipt', compact('sale'));
    }

    /**
     * Reprint receipt preserving original invoice number.
     */
    public function reprint(PosSale $sale)
    {
        $sale->load(['store', 'cashier', 'customer', 'promotion', 'items', 'payments']);
        return view('pos.receipt', ['sale' => $sale, 'isReprint' => true]);
    }

    /**
     * View Retail Sales History and Invoices with detailed breakdowns, filters, and totals.
     */
    public function salesHistory(Request $request)
    {
        $user = auth()->user();

        $query = PosSale::with(['store', 'cashier', 'customer', 'items.productVariant.product', 'payments'])
            ->latest('id');

        // Scoping for non-superadmin users
        if (($user->isStoreManager() || $user->isCashier()) && $user->store_id) {
            $query->where('store_id', $user->store_id);
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        // Search by invoice or customer phone/name
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $method = $request->input('payment_method');
            $query->whereHas('payments', function ($pq) use ($method) {
                $pq->where('payment_method', $method);
            });
        }

        // Filter by cashier
        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', $request->input('cashier_id'));
        }

        // Statistics aggregations based on current filter (before pagination)
        $statsQuery = clone $query;
        $totalSalesAmount = (float) $statsQuery->sum('total_payable');
        $totalInvoicesCount = $statsQuery->count();
        $totalDiscountAmount = (float) $statsQuery->sum('discount_amount');

        // Payment split totals
        $saleIds = (clone $query)->pluck('id');
        $cashTotal = (float) \App\Models\PosSalePayment::whereIn('pos_sale_id', $saleIds)
            ->where('payment_method', 'cash')
            ->sum('amount');
        $digitalTotal = (float) \App\Models\PosSalePayment::whereIn('pos_sale_id', $saleIds)
            ->whereIn('payment_method', ['bkash', 'nagad', 'rocket', 'card'])
            ->sum('amount');

        $sales = $query->paginate(20)->withQueryString();
        $stores = Store::orderBy('name')->get();
        $cashiers = \App\Models\User::whereIn('role', [
            \App\Enums\UserRole::CASHIER,
            \App\Enums\UserRole::STORE_MANAGER,
            \App\Enums\UserRole::SUPER_ADMIN,
        ])->orderBy('name')->get();

        return view('pos.history', compact(
            'sales',
            'stores',
            'cashiers',
            'totalSalesAmount',
            'totalInvoicesCount',
            'totalDiscountAmount',
            'cashTotal',
            'digitalTotal'
        ));
    }
}
