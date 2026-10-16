<?php

namespace App\Services;

use App\Models\DailyCashRegister;
use App\Models\Expense;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Compute comprehensive Income Statement (P&L) with Gross Profit, COGS (WAC), and Operating Profit.
     */
    public function getIncomeStatement(
        ?Store $store = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        // 1. Sales query
        $salesQuery = PosSale::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'partially_refunded', 'refunded']);

        if ($store) {
            $salesQuery->where('store_id', $store->id);
        }

        $grossSales = (float) $salesQuery->sum('subtotal_amount');
        $promotionalDiscounts = (float) $salesQuery->sum('discount_amount');
        $loyaltyDiscounts = (float) $salesQuery->sum('loyalty_discount_amount');
        $totalDiscounts = $promotionalDiscounts + $loyaltyDiscounts;
        $vatCollected = (float) $salesQuery->sum('tax_amount');
        $salesCount = $salesQuery->count();

        // 2. Returns query
        $returnsQuery = PosReturn::whereBetween('created_at', [$start, $end]);
        if ($store) {
            $returnsQuery->where('store_id', $store->id);
        }
        $totalReturnsValue = (float) $returnsQuery->sum('total_returned_value');
        $returnsCount = $returnsQuery->count();

        // Net Sales Revenue (Excludes VAT, as VAT belongs to tax authority)
        $netSalesRevenue = round(max(0, $grossSales - $totalDiscounts - $totalReturnsValue), 2);

        // 3. Cost of Goods Sold (COGS) via Weighted Average Cost (WAC)
        // Sold items COGS
        $soldItemsQuery = PosSaleItem::whereHas('sale', function ($q) use ($start, $end, $store) {
            $q->whereBetween('created_at', [$start, $end])
              ->whereIn('status', ['completed', 'partially_refunded', 'refunded']);
            if ($store) {
                $q->where('store_id', $store->id);
            }
        })->with('variant');

        $cogsSold = 0.0;
        foreach ($soldItemsQuery->get() as $item) {
            $unitWac = (float) ($item->variant->weighted_average_cost ?? $item->variant->purchase_price ?? 0);
            $cogsSold += $item->quantity * $unitWac;
        }

        // Restored sellable returns deduction from COGS
        $returnedItemsQuery = PosReturnItem::whereHas('posReturn', function ($q) use ($start, $end, $store) {
            $q->whereBetween('created_at', [$start, $end]);
            if ($store) {
                $q->where('store_id', $store->id);
            }
        })->where('condition', 'sellable')->with('variant');

        $cogsReturned = 0.0;
        foreach ($returnedItemsQuery->get() as $item) {
            $unitWac = (float) ($item->variant->weighted_average_cost ?? $item->variant->purchase_price ?? 0);
            $cogsReturned += $item->quantity * $unitWac;
        }

        $cogs = round(max(0, $cogsSold - $cogsReturned), 2);

        // 4. Gross Profit
        $grossProfit = round($netSalesRevenue - $cogs, 2);
        $grossProfitMargin = $netSalesRevenue > 0 ? round(($grossProfit / $netSalesRevenue) * 100, 2) : 0.0;

        // 5. Operating Expenses (Only approved or paid finalized expenses)
        $expensesQuery = Expense::whereBetween('expense_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereIn('status', ['approved', 'paid']);

        if ($store) {
            $expensesQuery->where('store_id', $store->id);
        }

        $totalOperatingExpenses = (float) $expensesQuery->sum('amount');

        // Expense by Category breakdown
        $expenseBreakdown = $expensesQuery->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(fn ($e) => [
                'category_name' => $e->category->name ?? 'Uncategorized',
                'amount' => (float) $e->total,
            ])->toArray();

        // 6. Net Operating Profit / Business Result
        $operatingProfit = round($grossProfit - $totalOperatingExpenses, 2);
        $operatingProfitMargin = $netSalesRevenue > 0 ? round(($operatingProfit / $netSalesRevenue) * 100, 2) : 0.0;

        return [
            'period' => [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'store_name' => $store ? $store->name : 'All Stores (Consolidated)',
            ],
            'gross_sales' => $grossSales,
            'discounts' => $totalDiscounts,
            'returns' => $totalReturnsValue,
            'net_sales_revenue' => $netSalesRevenue,
            'net_revenue' => $netSalesRevenue,
            'vat_collected' => $vatCollected,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_profit_margin_pct' => $grossProfitMargin,
            'gross_margin_percent' => $grossProfitMargin,
            'operating_expenses' => $totalOperatingExpenses,
            'expenses' => $totalOperatingExpenses,
            'expense_breakdown' => $expenseBreakdown,
            'operating_profit' => $operatingProfit,
            'operating_profit_margin_pct' => $operatingProfitMargin,
            'sales_count' => $salesCount,
            'returns_count' => $returnsCount,
        ];
    }

    /**
     * Store cash reconciliation and discrepancy breakdown.
     */
    public function getCashReconciliationReport(
        Store $store,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
        $end = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : now()->format('Y-m-d');

        $registers = DailyCashRegister::where('store_id', $store->id)
            ->whereBetween('business_date', [$start, $end])
            ->with(['opener', 'closer'])
            ->orderBy('business_date', 'desc')
            ->get();

        $totalOpeningCash = (float) $registers->sum('opening_cash');
        $totalCashSales = (float) $registers->sum('cash_sales_amount');
        $totalCashRefunds = (float) $registers->sum('cash_refunds_amount');
        $totalDiscrepancy = (float) $registers->sum('discrepancy_amount');

        return [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'start_date' => $start,
            'end_date' => $end,
            'total_opening_cash' => $totalOpeningCash,
            'total_cash_sales' => $totalCashSales,
            'total_cash_refunds' => $totalCashRefunds,
            'total_discrepancy' => $totalDiscrepancy,
            'registers' => $registers,
        ];
    }

    /**
     * Slow-Moving / Aging Stock Detection (Variants with stock on hand but 0 sales in threshold days).
     */
    public function getSlowMovingStockReport(?Store $store = null, int $thresholdDays = 30): array
    {
        $sinceDate = now()->subDays($thresholdDays)->startOfDay();

        $balancesQuery = StockBalance::where('quantity_on_hand', '>', 0)
            ->with(['variant.product.category', 'store']);

        if ($store) {
            $balancesQuery->where('store_id', $store->id);
        }

        $slowMovingItems = [];
        foreach ($balancesQuery->get() as $balance) {
            $variant = $balance->variant;
            if (!$variant) {
                continue;
            }

            // Check if sold recently
            $recentSalesCount = PosSaleItem::where('product_variant_id', $variant->id)
                ->whereHas('sale', function ($q) use ($sinceDate, $balance) {
                    $q->where('store_id', $balance->store_id)
                      ->where('created_at', '>=', $sinceDate);
                })
                ->sum('quantity');

            if ($recentSalesCount == 0) {
                $valuation = $balance->quantity_on_hand * (float) ($variant->weighted_average_cost ?? $variant->purchase_price ?? 0);
                $slowMovingItems[] = [
                    'store_name' => $balance->store->name,
                    'product_name' => $variant->product->name,
                    'variant_title' => "{$variant->color} - {$variant->size}",
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'quantity_on_hand' => $balance->quantity_on_hand,
                    'unit_cost' => (float) ($variant->weighted_average_cost ?? $variant->purchase_price ?? 0),
                    'tied_up_capital' => $valuation,
                    'days_without_sale' => $thresholdDays,
                ];
            }
        }

        return $slowMovingItems;
    }

    /**
     * Compare sales, expenses, and gross profit across multiple stores.
     */
    public function getStoreComparison(?string $startDate = null, ?string $endDate = null): array
    {
        $stores = Store::where('is_active', true)->where('is_warehouse', false)->get();
        $comparison = [];

        foreach ($stores as $s) {
            $statement = $this->getIncomeStatement($s, $startDate, $endDate);
            $comparison[] = [
                'store_id' => $s->id,
                'store_name' => $s->name,
                'store_code' => $s->code,
                'gross_sales' => $statement['gross_sales'],
                'net_revenue' => $statement['net_sales_revenue'],
                'cogs' => $statement['cogs'],
                'gross_profit' => $statement['gross_profit'],
                'operating_expenses' => $statement['operating_expenses'],
                'operating_profit' => $statement['operating_profit'],
                'sales_count' => $statement['sales_count'],
            ];
        }

        return $comparison;
    }
}
