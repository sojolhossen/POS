<?php

namespace App\Http\Controllers;

use App\Enums\StockTransactionType;
use App\Models\DailyCashRegister;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\PosReturn;
use App\Models\PosSale;
use App\Models\PosSalePayment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceiving;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        } elseif ($user->isStoreManager()) {
            return $this->storeManagerDashboard($user);
        } elseif ($user->isCashier()) {
            return $this->cashierDashboard($user);
        } elseif ($user->isWarehouseStaff()) {
            return $this->warehouseStaffDashboard($user);
        } elseif ($user->isAccountant()) {
            return $this->accountantDashboard();
        }

        return view('dashboard');
    }

    protected function superAdminDashboard()
    {
        $today = now()->format('Y-m-d');
        $financials = $this->reportService->getIncomeStatement();

        $stores = Store::where('is_active', true)->get();
        $supplierDues = (float) Supplier::sum('outstanding_balance');
        $pendingExpensesCount = Expense::where('status', 'pending_approval')->count();
        $pendingTransfersCount = StockTransfer::whereIn('status', ['requested', 'in_transit'])->count();
        $lowStockCount = StockBalance::where('quantity_on_hand', '<=', 5)->where('quantity_on_hand', '>', 0)->count();

        $financialAccounts = FinancialAccount::where('is_active', true)->get();
        $totalLiquidCash = (float) $financialAccounts->sum('current_balance');
        $openCashRegisters = DailyCashRegister::where('status', 'open')->with(['store', 'user'])->get();

        // 7-day trend series for Analytics charts
        $dates = collect();
        $revenueSeries = collect();
        $orderSeries = collect();
        $paymentSeries = [
            'Cash' => collect(),
            'bKash' => collect(),
            'Nagad' => collect(),
            'Card' => collect(),
        ];
        $stockSeries = [
            'sold' => collect(),
            'transferred' => collect(),
            'received' => collect(),
            'in_transit' => collect(),
            'reserved' => collect(),
        ];

        // Pre-fetch payments grouped by date & method for the last 7 days
        $startDate = now()->subDays(6)->startOfDay();
        $paymentsByDay = DB::table('pos_sale_payments')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as dt, LOWER(payment_method) as method, SUM(amount) as amt')
            ->groupBy('dt', 'method')
            ->get()
            ->groupBy('dt');

        // Pre-fetch stock ledgers grouped by date & type for the last 7 days
        $stockByDay = DB::table('stock_ledgers')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as dt, transaction_type, SUM(ABS(quantity)) as qty')
            ->groupBy('dt', 'transaction_type')
            ->get()
            ->groupBy('dt');

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $dates->push($date->format('M d'));

            // 1 & 2: Daily Sales and Orders
            $daySales = PosSale::whereDate('created_at', $dateString)
                ->whereIn('status', ['completed', 'partially_refunded', 'refunded']);

            $orderSeries->push((int) $daySales->count());
            $revenueSeries->push((float) $daySales->sum('total_payable'));

            // 3: Daily Payments by Tender
            $dayPayGroup = $paymentsByDay->get($dateString, collect())->mapWithKeys(function ($item) {
                return [(string) ($item->method ?? '') => (float) ($item->amt ?? 0)];
            });
            $paymentSeries['Cash']->push((float) ($dayPayGroup['cash'] ?? 0));
            $paymentSeries['bKash']->push((float) ($dayPayGroup['bkash'] ?? 0));
            $paymentSeries['Nagad']->push((float) ($dayPayGroup['nagad'] ?? 0));
            $paymentSeries['Card']->push((float) ($dayPayGroup['card'] ?? 0));

            // 4: Daily Stock Movements by Category
            $dayStockGroup = $stockByDay->get($dateString, collect())->mapWithKeys(function ($item) {
                $type = is_object($item->transaction_type) && isset($item->transaction_type->value)
                    ? $item->transaction_type->value
                    : (string) $item->transaction_type;
                return [$type => (int) ($item->qty ?? 0)];
            });

            $soldQty = (int) ($dayStockGroup[StockTransactionType::SALE->value] ?? 0);
            $transferQty = (int) (($dayStockGroup[StockTransactionType::TRANSFER_DISPATCH->value] ?? 0) + ($dayStockGroup[StockTransactionType::TRANSFER_RECEIVE->value] ?? 0));
            $receiveQty = (int) ($dayStockGroup[StockTransactionType::WAREHOUSE_RECEIVE->value] ?? 0);
            $adjustQty = (int) (($dayStockGroup[StockTransactionType::ADJUSTMENT_INCREASE->value] ?? 0) + ($dayStockGroup[StockTransactionType::ADJUSTMENT_DECREASE->value] ?? 0));
            $returnQty = (int) ($dayStockGroup[StockTransactionType::RETURN->value] ?? 0);

            $stockSeries['sold']->push($soldQty);
            $stockSeries['transferred']->push($transferQty);
            $stockSeries['received']->push($receiveQty);
            $stockSeries['in_transit']->push($adjustQty);
            $stockSeries['reserved']->push($returnQty);
        }

        // Summary totals for Payment tenders (overall)
        $paymentsTotal = PosSalePayment::selectRaw('LOWER(payment_method) as method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $paymentMethods = [
            'Cash' => (float) ($paymentsTotal['cash'] ?? 0),
            'bKash' => (float) ($paymentsTotal['bkash'] ?? 0),
            'Nagad' => (float) ($paymentsTotal['nagad'] ?? 0),
            'Card' => (float) ($paymentsTotal['card'] ?? 0),
        ];

        // Summary totals for Stock Movements (overall)
        $stockMovementTotals = [
            'sold' => (int) StockLedger::where('transaction_type', StockTransactionType::SALE->value)->sum(DB::raw('abs(quantity)')),
            'transferred' => (int) StockLedger::whereIn('transaction_type', [StockTransactionType::TRANSFER_DISPATCH->value, StockTransactionType::TRANSFER_RECEIVE->value])->sum(DB::raw('abs(quantity)')),
            'received' => (int) StockLedger::where('transaction_type', StockTransactionType::WAREHOUSE_RECEIVE->value)->sum(DB::raw('abs(quantity)')),
            'in_transit' => (int) StockTransferItem::whereHas('transfer', fn($q) => $q->whereIn('status', ['requested', 'in_transit']))->sum(DB::raw('COALESCE(NULLIF(quantity_dispatched, 0), quantity_requested)')),
            'reserved' => (int) StockBalance::sum('quantity_reserved'),
        ];

        return view('dashboards.super_admin', compact(
            'stores',
            'financials',
            'supplierDues',
            'pendingExpensesCount',
            'pendingTransfersCount',
            'lowStockCount',
            'financialAccounts',
            'totalLiquidCash',
            'openCashRegisters',
            'dates',
            'revenueSeries',
            'orderSeries',
            'paymentMethods',
            'paymentSeries',
            'stockSeries',
            'stockMovementTotals'
        ));
    }

    protected function storeManagerDashboard($user)
    {
        $store = $user->store ?? Store::first();
        $today = now()->format('Y-m-d');

        $financials = $this->reportService->getIncomeStatement($store);
        $cashRegister = DailyCashRegister::where('store_id', $store?->id)->where('business_date', $today)->first();

        $pendingTransfers = StockTransfer::where('destination_store_id', $store?->id)
            ->whereIn('status', ['requested', 'in_transit'])
            ->get();

        $lowStockItems = StockBalance::where('store_id', $store?->id)
            ->where('quantity_on_hand', '<=', 5)
            ->where('quantity_on_hand', '>', 0)
            ->with(['productVariant.product'])
            ->get();

        return view('dashboards.store_manager', compact(
            'store',
            'financials',
            'cashRegister',
            'pendingTransfers',
            'lowStockItems'
        ));
    }

    protected function cashierDashboard($user)
    {
        $store = $user->store ?? Store::first();
        $today = now()->format('Y-m-d');

        $activeSession = DailyCashRegister::where('store_id', $store?->id)
            ->where('opened_by', $user->id)
            ->where('status', 'open')
            ->first() ?? DailyCashRegister::where('store_id', $store?->id)
            ->where('business_date', $today)
            ->first();

        $recentOrders = PosSale::where('store_id', $store?->id)
            ->where('cashier_id', $user->id)
            ->whereDate('created_at', $today)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->with('customer')
            ->get();

        $todaySalesTotal = (float) PosSale::where('cashier_id', $user->id)->whereDate('created_at', $today)->sum('total_payable');
        $todayOrdersCount = PosSale::where('cashier_id', $user->id)->whereDate('created_at', $today)->count();

        return view('dashboards.cashier', compact('store', 'activeSession', 'recentOrders', 'todaySalesTotal', 'todayOrdersCount'));
    }

    protected function warehouseStaffDashboard($user)
    {
        $pendingPOs = PurchaseOrder::where('status', 'approved')->with('supplier')->get();
        $pendingDispatches = StockTransfer::where('status', 'approved')->with(['sourceStore', 'destinationStore'])->get();
        $inTransitTransfers = StockTransfer::where('status', 'in_transit')->with(['sourceStore', 'destinationStore'])->get();
        $lowStockItems = StockBalance::where('quantity_on_hand', '<=', 5)->where('quantity_on_hand', '>', 0)->with(['productVariant.product'])->get();
        $recentReceivings = PurchaseReceiving::with(['supplier', 'store'])->orderBy('id', 'desc')->limit(5)->get();

        return view('dashboards.warehouse_staff', compact(
            'pendingPOs',
            'pendingDispatches',
            'inTransitTransfers',
            'lowStockItems',
            'recentReceivings'
        ));
    }

    protected function accountantDashboard()
    {
        $financialAccounts = FinancialAccount::with('store')->where('is_active', true)->get();
        $totalLiquidCash = $financialAccounts->sum('current_balance');

        $supplierDues = (float) Supplier::sum('outstanding_balance');
        $pendingExpenses = Expense::where('status', 'pending_approval')->with(['store', 'category'])->get();
        $monthlyStatement = $this->reportService->getIncomeStatement();
        $todayRegisters = DailyCashRegister::where('status', 'open')->with(['store', 'user'])->get();

        return view('dashboards.accountant', compact(
            'financialAccounts',
            'totalLiquidCash',
            'supplierDues',
            'pendingExpenses',
            'monthlyStatement',
            'todayRegisters'
        ));
    }

    /**
     * Switch persistent language preference (Bangla/English).
     */
    public function setLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:en,bn'],
        ]);

        $locale = $validated['locale'];
        $request->session()->put('locale', $locale);

        if ($user = $request->user()) {
            $user->locale = $locale;
            $user->save();
        }

        return back();
    }

    /**
     * Switch persistent theme preference (Light/Dark).
     */
    public function setTheme(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['required', 'in:light,dark'],
        ]);

        $theme = $validated['theme'];
        $request->session()->put('theme', $theme);

        if ($user = $request->user()) {
            $user->theme = $theme;
            $user->save();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'theme' => $theme]);
        }

        return back();
    }
}
