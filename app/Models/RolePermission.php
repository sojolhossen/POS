<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'module',
        'is_allowed',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    /**
     * Get default permissions mapping for all roles
     */
    public static function defaultPermissions(): array
    {
        return [
            'pos' => [
                'name' => 'Point of Sale (POS)',
                'description' => 'Create checkout sales, scan barcodes, print customer receipts',
                'category' => 'Retail Operations',
                'defaults' => ['super_admin', 'store_manager', 'cashier'],
            ],
            'sales_history' => [
                'name' => 'Sales History & Invoices',
                'description' => 'View past retail invoices, customer orders, payment breakdown, reprint receipts',
                'category' => 'Retail Operations',
                'defaults' => ['super_admin', 'store_manager', 'cashier', 'accountant'],
            ],
            'returns' => [
                'name' => 'Returns & Exchanges',
                'description' => 'Process customer product returns, refunds, item exchanges',
                'category' => 'Retail Operations',
                'defaults' => ['super_admin', 'store_manager', 'cashier'],
            ],
            'daily_registers' => [
                'name' => 'Daily Cash Registers',
                'description' => 'Open day cash, close register, record cash audits',
                'category' => 'Retail Operations',
                'defaults' => ['super_admin', 'store_manager', 'cashier'],
            ],
            'stores' => [
                'name' => 'Retail Stores & Outlets',
                'description' => 'Manage physical stores, outlets, store addresses',
                'category' => 'Retail Operations',
                'defaults' => ['super_admin', 'store_manager'],
            ],
            'transfers' => [
                'name' => 'Stock Transfers',
                'description' => 'Inter-store inventory transfers, dispatch, and receiving',
                'category' => 'Retail Operations',
                'defaults' => ['super_admin', 'store_manager', 'warehouse_staff'],
            ],
            'suppliers' => [
                'name' => 'Suppliers & Vendors',
                'description' => 'Supplier profiles, ledger balance, and payment settlement',
                'category' => 'Purchasing & Vendors',
                'defaults' => ['super_admin', 'store_manager', 'accountant'],
            ],
            'purchase_orders' => [
                'name' => 'Purchase Orders & Intake',
                'description' => 'Create POs, approve intake, physical stock receiving',
                'category' => 'Purchasing & Vendors',
                'defaults' => ['super_admin', 'store_manager', 'warehouse_staff'],
            ],
            'supplier_returns' => [
                'name' => 'Supplier Returns',
                'description' => 'Return defective or excess stock to vendor',
                'category' => 'Purchasing & Vendors',
                'defaults' => ['super_admin', 'store_manager', 'warehouse_staff'],
            ],
            'categories' => [
                'name' => 'Categories & Hierarchy',
                'description' => 'Product categories and subcategories management',
                'category' => 'Catalog & Promotions',
                'defaults' => ['super_admin', 'store_manager'],
            ],
            'products' => [
                'name' => 'Product Catalog & Variants',
                'description' => 'Create styles, SKUs, sizes, colors, initial stocks',
                'category' => 'Catalog & Promotions',
                'defaults' => ['super_admin', 'store_manager'],
            ],
            'barcodes' => [
                'name' => 'Barcode Labels & Printing',
                'description' => 'Hardware scanner test, batch barcode label printing',
                'category' => 'Catalog & Promotions',
                'defaults' => ['super_admin', 'store_manager', 'warehouse_staff'],
            ],
            'promotions' => [
                'name' => 'Promotions & Discounts',
                'description' => 'Scheduled promotions, discount percentage rules',
                'category' => 'Catalog & Promotions',
                'defaults' => ['super_admin'],
            ],
            'inventory' => [
                'name' => 'Stock Balances & Ledger',
                'description' => 'View stock quantities on hand across all locations',
                'category' => 'Inventory & Ledger',
                'defaults' => ['super_admin', 'store_manager', 'warehouse_staff', 'accountant'],
            ],
            'stock_adjustments' => [
                'name' => 'Stock Adjustments',
                'description' => 'Request inventory write-offs, damages, corrections',
                'category' => 'Inventory & Ledger',
                'defaults' => ['super_admin', 'store_manager', 'warehouse_staff'],
            ],
            'stock_counts' => [
                'name' => 'Physical Stock Audits',
                'description' => 'Periodic inventory cycle counts, scanner reconciliation',
                'category' => 'Inventory & Ledger',
                'defaults' => ['super_admin', 'store_manager', 'warehouse_staff'],
            ],
            'financial_accounts' => [
                'name' => 'Financial Accounts',
                'description' => 'Cash boxes, bank accounts, mobile financial services (bKash/Nagad)',
                'category' => 'Accounting & Finance',
                'defaults' => ['super_admin', 'accountant'],
            ],
            'expenses' => [
                'name' => 'Expenses & Approvals',
                'description' => 'Daily operational store expenses and payout approvals',
                'category' => 'Accounting & Finance',
                'defaults' => ['super_admin', 'store_manager', 'accountant'],
            ],
            'reports' => [
                'name' => 'Profit & Loss & Reports',
                'description' => 'Executive financial reporting, store comparison, slow moving goods',
                'category' => 'Accounting & Finance',
                'defaults' => ['super_admin', 'accountant'],
            ],
            'employees' => [
                'name' => 'Employees & Attendance',
                'description' => 'Staff roster, daily check-in attendance, monthly payroll',
                'category' => 'Staff & Payroll',
                'defaults' => ['super_admin', 'store_manager', 'accountant'],
            ],
            'customers' => [
                'name' => 'Customers & Loyalty',
                'description' => 'Customer directory, purchase history, loyalty point ledger',
                'category' => 'Customers & Administration',
                'defaults' => ['super_admin', 'store_manager', 'cashier'],
            ],
            'staff' => [
                'name' => 'Staff Accounts & Roles',
                'description' => 'Create user logins, assign roles, reset credentials',
                'category' => 'Customers & Administration',
                'defaults' => ['super_admin'],
            ],
            'audit' => [
                'name' => 'Audit Trails & Activity Logs',
                'description' => 'Tamper-evident logs of all system activities and approvals',
                'category' => 'Customers & Administration',
                'defaults' => ['super_admin'],
            ],
        ];
    }

    /**
     * Check if a specific role is permitted to access a given module.
     * Super Admin always has full bypass access.
     */
    public static function isAllowed(string $role, string $module): bool
    {
        if ($role === 'super_admin') {
            return true;
        }

        $record = static::where('role', $role)->where('module', $module)->first();
        if ($record !== null) {
            return (bool) $record->is_allowed;
        }

        // Fallback to sensible defaults
        $defs = static::defaultPermissions();
        if (isset($defs[$module])) {
            return in_array($role, $defs[$module]['defaults'], true);
        }

        return false;
    }
}
