<?php

namespace App\Http\Controllers;

use App\Models\DailyCashRegister;
use App\Models\Store;
use App\Services\ExportService;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService,
        protected ExportService $exportService
    ) {}

    /**
     * Income Statement (P&L) Report.
     */
    public function financial(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $storeId = ($user->isStoreManager() && $user->store_id)
            ? $user->store_id
            : $request->input('store_id');

        $store = $storeId ? Store::find($storeId) : null;
        $statement = $this->reportService->getIncomeStatement($store, $startDate, $endDate);
        $report = array_merge($statement, [
            'net_revenue' => $statement['net_sales_revenue'],
            'expenses' => $statement['operating_expenses'],
            'gross_margin_percent' => $statement['gross_profit_margin_pct'],
        ]);

        $stores = ($user->isStoreManager() && $user->store_id)
            ? Store::where('id', $user->store_id)->get()
            : Store::where('is_active', true)->get();

        return view('reports.financial', compact('statement', 'report', 'startDate', 'endDate', 'store', 'stores', 'storeId'));
    }

    /**
     * Daily Cash Register Reconciliation Report.
     */
    public function cash(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $storeId = ($user->isStoreManager() && $user->store_id)
            ? $user->store_id
            : ($request->input('store_id') ?: Store::first()?->id);

        $store = Store::find($storeId) ?? Store::first();
        $reconciliation = $store ? $this->reportService->getCashReconciliationReport($store, $startDate, $endDate) : [];
        $registers = DailyCashRegister::when($store, fn($q) => $q->where('store_id', $store->id))
            ->whereBetween('business_date', [$startDate, $endDate])
            ->with(['opener', 'closer'])
            ->orderBy('business_date', 'desc')
            ->paginate(15);
        $date = $endDate;

        $stores = ($user->isStoreManager() && $user->store_id)
            ? Store::where('id', $user->store_id)->get()
            : Store::where('is_active', true)->get();

        return view('reports.cash', compact('reconciliation', 'registers', 'startDate', 'endDate', 'date', 'store', 'stores', 'storeId'));
    }

    /**
     * Slow-Moving and Dead Stock Report.
     */
    public function slowMoving(Request $request)
    {
        $user = auth()->user();
        $days = (int) $request->input('days', 30);
        $daysThreshold = $days;

        $storeId = ($user->isStoreManager() && $user->store_id)
            ? $user->store_id
            : $request->input('store_id');

        $store = $storeId ? Store::find($storeId) : null;
        $items = $this->reportService->getSlowMovingStockReport($store, $days);

        $stores = ($user->isStoreManager() && $user->store_id)
            ? Store::where('id', $user->store_id)->get()
            : Store::where('is_active', true)->get();

        return view('reports.slow_moving', compact('items', 'daysThreshold', 'days', 'store', 'stores', 'storeId'));
    }

    /**
     * Cross-Store Performance Comparison Matrix.
     */
    public function storeComparison(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isAccountant()) {
            abort(403, "Access to cross-store financial comparisons is restricted to Super Admin and Accountants.");
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $comparisons = $this->reportService->getStoreComparison($startDate, $endDate);
        $comparison = $comparisons;

        return view('reports.store_comparison', compact('comparisons', 'comparison', 'startDate', 'endDate'));
    }

    /**
     * Secure CSV Export with Spreadsheet Formula Injection Protection.
     */
    public function export(Request $request)
    {
        $reportType = $request->input('type');
        $user = auth()->user();

        switch ($reportType) {
            case 'financial':
                $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
                $endDate = $request->input('end_date', now()->format('Y-m-d'));
                $store = $request->filled('store_id') ? Store::find($request->store_id) : null;

                $data = $this->reportService->getIncomeStatement($store, $startDate, $endDate);

                $headers = ['Metric', 'Amount (BDT)'];
                $rows = [
                    ['Gross Sales', $data['gross_sales']],
                    ['Discounts & Promos', $data['discounts']],
                    ['Returns & Exchanges', $data['returns']],
                    ['Net Sales Revenue', $data['net_sales_revenue']],
                    ['VAT Collected (5%)', $data['vat_collected']],
                    ['Cost of Goods Sold (COGS)', $data['cogs']],
                    ['Gross Profit', $data['gross_profit']],
                    ['Operating Expenses', $data['operating_expenses']],
                    ['Operating Profit', $data['operating_profit']],
                ];

                return $this->exportService->streamCsv("income_statement_{$startDate}_{$endDate}.csv", $headers, $rows);

            case 'slow_moving':
                $days = (int) $request->input('days', 30);
                $store = $request->filled('store_id') ? Store::find($request->store_id) : null;
                $items = $this->reportService->getSlowMovingStockReport($store, $days);

                $headers = ['Store', 'Product', 'Variant', 'SKU', 'Barcode', 'Available Stock', 'Unit Cost (BDT)', 'Tied Capital (BDT)'];
                $rows = array_map(fn ($i) => [
                    $i['store_name'],
                    $i['product_name'],
                    $i['variant_title'],
                    $i['sku'],
                    $i['barcode'],
                    $i['quantity_on_hand'],
                    $i['unit_cost'],
                    $i['tied_up_capital'],
                ], $items);

                return $this->exportService->streamCsv("slow_moving_stock_{$days}_days.csv", $headers, $rows);

            case 'comparison':
                $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
                $endDate = $request->input('end_date', now()->format('Y-m-d'));
                $data = $this->reportService->getStoreComparison($startDate, $endDate);

                $headers = ['Store', 'Code', 'Total Orders', 'Net Revenue (BDT)', 'COGS (WAC)', 'Gross Profit (BDT)', 'Operating Expenses (BDT)', 'Operating Profit (BDT)'];
                $rows = array_map(fn ($s) => [
                    $s['store_name'],
                    $s['store_code'],
                    $s['total_orders'] ?? $s['sales_count'] ?? 0,
                    $s['net_revenue'],
                    $s['cogs'],
                    $s['gross_profit'],
                    $s['expenses'] ?? $s['operating_expenses'] ?? 0,
                    $s['operating_profit'],
                ], $data);

                return $this->exportService->streamCsv("store_comparison_{$startDate}_{$endDate}.csv", $headers, $rows);

            default:
                abort(404, "Unknown report type for export.");
        }
    }
}
