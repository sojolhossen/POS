<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BrandingSettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConnectivityController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyCashRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosReturnController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReceivingController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierReturnController;
use App\Http\Controllers\TaxSettingController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

// Connectivity heartbeat
Route::get('/api/ping', [ConnectivityController::class, 'ping'])->name('api.ping');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

// Authenticated application routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Language & Theme Switchers
    Route::post('/set-locale', [DashboardController::class, 'setLocale'])->name('locale.set');
    Route::post('/set-theme', [DashboardController::class, 'setTheme'])->name('theme.set');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Stores Management
    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/{store}', [StoreController::class, 'show'])->middleware('store.scope')->name('stores.show');

    // Super Admin Store routes
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('/admin/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/admin/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('/admin/stores/{store}', [StoreController::class, 'update'])->name('stores.update');

        // Staff Accounts Management (Super Admin ONLY)
        Route::resource('staff', StaffController::class)->except(['show', 'destroy']);
        Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');

        // Role & Module Permission Access Controls (Shop Owner / Super Admin ONLY)
        Route::get('/admin/roles-permissions', [RolePermissionController::class, 'index'])->name('roles.permissions.index');
        Route::post('/admin/roles-permissions', [RolePermissionController::class, 'update'])->name('roles.permissions.update');
        Route::post('/admin/roles-permissions/reset', [RolePermissionController::class, 'resetDefaults'])->name('roles.permissions.reset');

        // Tax / VAT Settings (Shop Owner / Super Admin ONLY)
        Route::get('/admin/tax-settings', [TaxSettingController::class, 'index'])->name('admin.tax.index');
        Route::post('/admin/tax-settings', [TaxSettingController::class, 'update'])->name('admin.tax.update');

        // Site Branding & Identity Settings (Shop Owner / Super Admin ONLY)
        Route::get('/admin/branding-settings', [BrandingSettingController::class, 'index'])->name('admin.branding.index');
        Route::post('/admin/branding-settings', [BrandingSettingController::class, 'update'])->name('admin.branding.update');

        // Audit Trail (Super Admin ONLY)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

        // Opening Stock Balance initial setup
        Route::get('/inventory/opening-stock', [InventoryController::class, 'createOpeningStock'])->name('inventory.opening-stock.create');
        Route::post('/inventory/opening-stock', [InventoryController::class, 'storeOpeningStock'])->name('inventory.opening-stock.store');
    });

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::middleware('role:super_admin,store_manager')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Products & Variants
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::middleware('role:super_admin,store_manager')->group(function () {
        Route::get('/admin/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/admin/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/admin/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('/admin/products/{product}/variants', [ProductController::class, 'addVariant'])->name('products.variants.add');
    });

    // Selling Price Update (Super Admin ONLY)
    Route::post('/admin/products/{product}/price', [ProductController::class, 'updatePrice'])
        ->middleware('role:super_admin')
        ->name('products.price.update');

    // Barcode Labels & Scanner Workstation
    Route::get('/barcodes', [BarcodeController::class, 'index'])->name('barcodes.index');
    Route::match(['get', 'post'], '/barcodes/print', [BarcodeController::class, 'printLabels'])->name('barcodes.print');
    Route::post('/barcodes/scan-lookup', [BarcodeController::class, 'scanLookup'])->name('barcodes.scan-lookup');

    // Inventory & Ledger
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/ledger', [InventoryController::class, 'ledger'])->name('inventory.ledger');

    // Central Warehouse Intake
    Route::middleware('role:super_admin,warehouse_staff')->group(function () {
        Route::get('/inventory/warehouse-intake', [InventoryController::class, 'createReceiveWarehouse'])->name('inventory.warehouse.create');
        Route::post('/inventory/warehouse-intake', [InventoryController::class, 'storeReceiveWarehouse'])->name('inventory.warehouse.store');
    });

    // Stock Adjustments
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');

    // Super Admin Stock Adjustment Approvals
    Route::middleware('role:super_admin')->group(function () {
        Route::post('/stock-adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve');
        Route::post('/stock-adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('stock-adjustments.reject');
    });

    // Physical Stock Counts
    Route::get('/stock-counts', [StockCountController::class, 'index'])->name('stock-counts.index');
    Route::get('/stock-counts/create', [StockCountController::class, 'create'])->name('stock-counts.create');
    Route::post('/stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
    Route::get('/stock-counts/{stockCount}', [StockCountController::class, 'show'])->name('stock-counts.show');
    Route::post('/stock-counts/{stockCount}/items', [StockCountController::class, 'recordItem'])->name('stock-counts.items.record');
    Route::post('/stock-counts/{stockCount}/complete', [StockCountController::class, 'complete'])->name('stock-counts.complete');

    // ==========================================
    // MASTER PROMPT 2 ROUTES
    // ==========================================

    // Supplier Management (Super Admin Exclusive Management, Financials visible to Super Admin and Accountant)
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/admin/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/admin/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/admin/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    });

    Route::post('/suppliers/{supplier}/payments', [SupplierController::class, 'recordPayment'])
        ->middleware('role:super_admin,accountant')
        ->name('suppliers.payments.store');

    // Purchase Orders
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');

    Route::middleware('role:super_admin,warehouse_staff')->group(function () {
        Route::get('/admin/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/admin/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

        // Purchase Receiving Workstation
        Route::get('/purchase-orders/{purchaseOrder}/receive', [PurchaseReceivingController::class, 'create'])->name('purchase-receivings.create');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseReceivingController::class, 'store'])->name('purchase-receivings.store');
    });

    // Super Admin PO Approval ONLY
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->middleware('role:super_admin')
        ->name('purchase-orders.approve');

    // Supplier Returns
    Route::get('/supplier-returns', [SupplierReturnController::class, 'index'])->name('supplier-returns.index');
    Route::get('/supplier-returns/{supplierReturn}', [SupplierReturnController::class, 'show'])->name('supplier-returns.show');
    Route::middleware('role:super_admin,warehouse_staff')->group(function () {
        Route::get('/admin/supplier-returns/create', [SupplierReturnController::class, 'create'])->name('supplier-returns.create');
        Route::post('/admin/supplier-returns', [SupplierReturnController::class, 'store'])->name('supplier-returns.store');
    });

    // Warehouse & Inter-Store Transfers
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
    Route::get('/transfers/{transfer}/print', [TransferController::class, 'printChallan'])->name('transfers.print');

    // Super Admin Stock Transfer Approval ONLY
    Route::post('/transfers/{transfer}/approve', [TransferController::class, 'approve'])
        ->middleware('role:super_admin')
        ->name('transfers.approve');

    Route::get('/transfers/{transfer}/dispatch', [TransferController::class, 'dispatchView'])
        ->middleware('role:super_admin,store_manager,warehouse_staff')
        ->name('transfers.dispatch.view');
    Route::post('/transfers/{transfer}/dispatch', [TransferController::class, 'dispatchStore'])
        ->middleware('role:super_admin,store_manager,warehouse_staff')
        ->name('transfers.dispatch.store');

    // Receiving at Destination Store (Any authorized user of destination store or Super Admin)
    Route::get('/transfers/{transfer}/receive', [TransferController::class, 'receiveView'])
        ->middleware('role:super_admin,store_manager,warehouse_staff,cashier')
        ->name('transfers.receive.view');
    Route::post('/transfers/{transfer}/receive', [TransferController::class, 'receiveStore'])
        ->middleware('role:super_admin,store_manager,warehouse_staff,cashier')
        ->name('transfers.receive.store');

    // Point of Sale (POS) Checkout (Authorized front-of-house roles ONLY)
    Route::middleware(['role:super_admin,store_manager,cashier', 'module:pos'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/search', [PosController::class, 'searchProduct'])->name('pos.search');
        Route::get('/pos/customers/search', [PosController::class, 'searchCustomer'])->name('pos.customers.search');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('/pos/receipt/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');
        Route::get('/pos/reprint/{sale}', [PosController::class, 'reprint'])->name('pos.reprint');
    });

    // Retail Sales History & Invoices
    Route::middleware('module:sales_history')->group(function () {
        Route::get('/pos/sales-history', [PosController::class, 'salesHistory'])->name('pos.sales.history');
    });

    // POS Returns & Exchanges
    Route::get('/returns', [PosReturnController::class, 'index'])->name('returns.index');
    Route::get('/returns/create', [PosReturnController::class, 'lookup'])->name('returns.create');
    Route::post('/returns', [PosReturnController::class, 'store'])->name('returns.store');
    Route::get('/returns/receipt/{posReturn}', [PosReturnController::class, 'receipt'])->name('returns.receipt');

    // Daily Cash Registers
    Route::get('/daily-registers', [DailyCashRegisterController::class, 'index'])->name('daily-registers.index');
    Route::post('/daily-registers/open', [DailyCashRegisterController::class, 'open'])->name('daily-registers.open');
    Route::post('/daily-registers/{register}/close', [DailyCashRegisterController::class, 'close'])->name('daily-registers.close');
    Route::post('/daily-registers/{register}/reopen', [DailyCashRegisterController::class, 'reopen'])
        ->middleware('role:super_admin,store_manager')
        ->name('daily-registers.reopen');

    // Scheduled Promotions (Super Admin ONLY)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::get('/promotions/create', [PromotionController::class, 'create'])->name('promotions.create');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::post('/promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle');
    });

    // Customers & Loyalty
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // ==========================================
    // MASTER PROMPT 3 ROUTES
    // ==========================================

    // Financial Accounts (Super Admin & Accountant)
    Route::middleware('role:super_admin,accountant')->group(function () {
        Route::get('/financial-accounts', [\App\Http\Controllers\FinancialAccountController::class, 'index'])->name('financial-accounts.index');
        Route::post('/financial-accounts', [\App\Http\Controllers\FinancialAccountController::class, 'store'])->name('financial-accounts.store');
        Route::post('/financial-accounts/transfer', [\App\Http\Controllers\FinancialAccountController::class, 'transfer'])->name('financial-accounts.transfer');
        Route::post('/financial-accounts/transactions/{transaction}/reconcile', [\App\Http\Controllers\FinancialAccountController::class, 'reconcile'])->name('financial-accounts.reconcile');
    });

    // Expense Management
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [\App\Http\Controllers\ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('/expenses/{expense}/approve', [\App\Http\Controllers\ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::post('/expenses/{expense}/reject', [\App\Http\Controllers\ExpenseController::class, 'reject'])->name('expenses.reject');
    Route::post('/expenses/{expense}/pay', [\App\Http\Controllers\ExpenseController::class, 'pay'])->name('expenses.pay');

    // Employee Records
    Route::get('/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [\App\Http\Controllers\EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [\App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'show'])->name('employees.show');

    // Attendance
    Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [\App\Http\Controllers\AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/{attendance}/correct', [\App\Http\Controllers\AttendanceController::class, 'correct'])->name('attendance.correct');

    // Payroll & Salary Advances
    Route::get('/payroll', [\App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/advance', [\App\Http\Controllers\PayrollController::class, 'advance'])->name('payroll.advance');
    Route::post('/payroll/pay', [\App\Http\Controllers\PayrollController::class, 'pay'])->name('payroll.pay');

    // Financial Reports & Exports
    Route::get('/reports/financial', [\App\Http\Controllers\ReportController::class, 'financial'])->name('reports.financial');
    Route::get('/reports/cash', [\App\Http\Controllers\ReportController::class, 'cash'])->name('reports.cash');
    Route::get('/reports/slow-moving', [\App\Http\Controllers\ReportController::class, 'slowMoving'])->name('reports.slow-moving');
    Route::get('/reports/slow_moving', [\App\Http\Controllers\ReportController::class, 'slowMoving'])->name('reports.slow_moving');
    Route::get('/reports/store-comparison', [\App\Http\Controllers\ReportController::class, 'storeComparison'])->name('reports.store-comparison');
    Route::get('/reports/store_comparison', [\App\Http\Controllers\ReportController::class, 'storeComparison'])->name('reports.store_comparison');
    Route::get('/reports/export', [\App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/inventory', [\App\Http\Controllers\ReportController::class, 'slowMoving'])->name('reports.inventory');

    // Controlled Data Imports (Super Admin ONLY)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/imports', [\App\Http\Controllers\DataImportController::class, 'index'])->name('imports.index');
        Route::post('/imports/upload', [\App\Http\Controllers\DataImportController::class, 'upload'])->name('imports.upload');
        Route::get('/imports/{import}/preview', [\App\Http\Controllers\DataImportController::class, 'preview'])->name('imports.preview');
        Route::post('/imports/{import}/confirm', [\App\Http\Controllers\DataImportController::class, 'confirm'])->name('imports.confirm');
    });

    // Cash register route alias for notifications
    Route::get('/cash-register', fn() => redirect()->route('daily-registers.index'))->name('cash-register.index');
});
