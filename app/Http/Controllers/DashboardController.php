<?php

namespace App\Http\Controllers;

use App\Enums\StockAdjustmentStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display role-specific dashboard with real, non-fabricated metrics.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $metrics = [
            'role' => $user->role,
            'store' => $user->store,
        ];

        if ($user->isSuperAdmin()) {
            $metrics['total_stores'] = Store::retailOnly()->count();
            $metrics['total_warehouses'] = Store::warehouseOnly()->count();
            $metrics['total_staff'] = User::count();
            $metrics['total_products'] = Product::count();
            $metrics['total_variants'] = ProductVariant::count();
            $metrics['pending_adjustments'] = StockAdjustment::where('status', StockAdjustmentStatus::PENDING)->count();
            $metrics['low_stock_items'] = StockBalance::whereRaw('quantity_on_hand <= reorder_level')->count();
            $metrics['recent_audits'] = AuditLog::with('user')->latest('id')->limit(8)->get();
            $metrics['recent_price_changes'] = PriceHistory::with(['product', 'user'])->latest('id')->limit(5)->get();
            $metrics['stores_list'] = Store::withCount('staff')->with('manager')->get();
        } elseif ($user->isStoreManager()) {
            $storeId = $user->store_id;
            $metrics['store_stock_on_hand'] = StockBalance::where('store_id', $storeId)->sum('quantity_on_hand');
            $metrics['store_low_stock'] = StockBalance::where('store_id', $storeId)->whereRaw('quantity_on_hand <= reorder_level')->count();
            $metrics['store_pending_adjustments'] = StockAdjustment::where('store_id', $storeId)->where('status', StockAdjustmentStatus::PENDING)->count();
            $metrics['store_staff_count'] = User::where('store_id', $storeId)->count();
            $metrics['recent_movements'] = StockLedger::where('store_id', $storeId)->with('variant.product')->latest('id')->limit(8)->get();
        } elseif ($user->isWarehouseStaff()) {
            $warehouseIds = Store::warehouseOnly()->pluck('id')->toArray();
            $metrics['warehouse_stock_on_hand'] = StockBalance::whereIn('store_id', $warehouseIds)->sum('quantity_on_hand');
            $metrics['recent_intakes'] = StockLedger::whereIn('store_id', $warehouseIds)
                ->where('transaction_type', 'warehouse_receive')
                ->with('variant.product')
                ->latest('id')
                ->limit(8)
                ->get();
            $metrics['warehouses'] = Store::warehouseOnly()->get();
        } elseif ($user->isAccountant()) {
            $metrics['total_products'] = Product::count();
            $metrics['total_variants'] = ProductVariant::count();
            // Real valuation calculated from product variants and balances
            $valuation = DB::table('stock_balances')
                ->join('product_variants', 'stock_balances.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->selectRaw('
                    SUM(stock_balances.quantity_on_hand * COALESCE(product_variants.purchase_price, products.base_purchase_price, 0)) as total_cost_value,
                    SUM(stock_balances.quantity_on_hand * COALESCE(product_variants.selling_price, products.base_selling_price, 0)) as total_retail_value
                ')
                ->first();

            $metrics['total_cost_value'] = $valuation->total_cost_value ?? 0;
            $metrics['total_retail_value'] = $valuation->total_retail_value ?? 0;
            $metrics['recent_price_changes'] = PriceHistory::with(['product', 'user'])->latest('id')->limit(10)->get();
        } else {
            // Cashier role
            $storeId = $user->store_id;
            $metrics['assigned_store'] = $user->store;
            $metrics['store_stock_on_hand'] = StockBalance::where('store_id', $storeId)->sum('quantity_on_hand');
            $metrics['quick_variants'] = ProductVariant::with('product')->active()->limit(10)->get();
        }

        return view('dashboard', compact('metrics'));
    }

    /**
     * Switch persistent language preference (Bangla/English).
     */
    public function setLocale(Request $request): RedirectResponse
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
    public function setTheme(Request $request): RedirectResponse
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

        return back();
    }
}
