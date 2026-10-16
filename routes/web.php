<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConnectivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

// Connectivity heartbeat
Route::get('/api/ping', [ConnectivityController::class, 'ping'])->name('api.ping');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
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
    Route::post('/barcodes/print', [BarcodeController::class, 'printLabels'])->name('barcodes.print');
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
});
