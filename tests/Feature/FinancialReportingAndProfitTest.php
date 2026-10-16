<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DailyCashRegister;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportingAndProfitTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $superAdmin;
    protected User $accountant;
    protected ProductVariant $variant;
    protected FinancialReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Uttara Hub', 'code' => 'ST-UTT', 'is_active' => true]);

        $this->superAdmin = User::create([
            'name' => 'Admin', 'email' => 'admin@pos.test', 'password' => bcrypt('password'),
            'role' => 'super_admin', 'is_active' => true,
        ]);

        $this->accountant = User::create([
            'name' => 'Accountant', 'email' => 'acc@pos.test', 'password' => bcrypt('password'),
            'role' => 'accountant', 'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Apparel', 'code' => 'APP-01', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Premium Panjabi',
            'sku' => 'PANJABI-001',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'barcode' => '894000112233',
            'size' => 'L',
            'color' => 'Black',
            'purchase_price' => 1500.00,
            'weighted_average_cost' => 1500.00, // WAC base cost
            'selling_price' => 3000.00,
            'is_active' => true,
        ]);

        $this->reportService = app(FinancialReportService::class);
    }

    public function test_accurate_p_and_l_accounting_with_wac_and_tax_separation(): void
    {
        // 1. Create a posted POS sale:
        // 2 units @ 3000 = 6000 gross. Tax (5% VAT) = 300. Total = 6300.
        $sale = PosSale::create([
            'invoice_number' => 'INV-UTT-0001',
            'store_id' => $this->store->id,
            'cashier_id' => $this->superAdmin->id,
            'subtotal_amount' => 6000.00,
            'discount_amount' => 0.00,
            'tax_rate' => 5.00,
            'tax_amount' => 300.00, // 5% statutory tax liability
            'total_payable' => 6300.00,
            'total_paid' => 6300.00,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        PosSaleItem::create([
            'pos_sale_id' => $sale->id,
            'product_variant_id' => $this->variant->id,
            'barcode' => $this->variant->barcode,
            'product_name' => 'Premium Panjabi',
            'size' => 'L',
            'color' => 'Black',
            'quantity' => 2,
            'unit_price' => 3000.00,
            'line_total' => 6000.00,
        ]);

        // 2. Customer return of 1000 BDT
        $return = PosReturn::create([
            'return_number' => 'RET-UTT-0001',
            'original_pos_sale_id' => $sale->id,
            'store_id' => $this->store->id,
            'processed_by' => $this->superAdmin->id,
            'return_type' => 'refund',
            'total_returned_value' => 1000.00,
            'net_settlement_amount' => 1000.00,
            'settlement_method' => 'cash',
            'reason' => 'Customer sizing return',
            'created_at' => now(),
        ]);

        // 3. Approved operational expense of 800 BDT
        $cat = ExpenseCategory::create(['name' => 'Store Rent', 'code' => 'RENT-01', 'is_active' => true]);
        Expense::create([
            'expense_number' => 'EXP-UTT-0001',
            'store_id' => $this->store->id,
            'expense_category_id' => $cat->id,
            'created_by' => $this->superAdmin->id,
            'title' => 'Monthly Store Utility',
            'amount' => 800.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        // Unapproved expense of 500 BDT (must NOT affect official profit)
        Expense::create([
            'expense_number' => 'EXP-UTT-0002',
            'store_id' => $this->store->id,
            'expense_category_id' => $cat->id,
            'created_by' => $this->superAdmin->id,
            'title' => 'Pending Repair',
            'amount' => 500.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'pending_approval',
        ]);

        $report = $this->reportService->getIncomeStatement(
            $this->store,
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        // Net Sales Revenue = Gross (6000) - Returns (1000) = 5000.00 (tax excluded)
        $this->assertEquals(5000.00, $report['net_sales_revenue']);

        // COGS = 2 units * 1500 (WAC cost) = 3000.00
        $this->assertEquals(3000.00, $report['cogs']);

        // Gross Profit = 5000 - 3000 = 2000.00
        $this->assertEquals(2000.00, $report['gross_profit']);

        // Approved Expenses = 800.00 (pending 500 excluded)
        $this->assertEquals(800.00, $report['operating_expenses']);

        // Operating Profit = 2000 - 800 = 1200.00
        $this->assertEquals(1200.00, $report['operating_profit']);

        // VAT Collected = 300.00 (segregated liability)
        $this->assertEquals(300.00, $report['vat_collected']);
    }

    public function test_daily_cash_reconciliation_detects_shortages_accurately(): void
    {
        DailyCashRegister::create([
            'store_id' => $this->store->id,
            'business_date' => now()->toDateString(),
            'opened_by' => $this->superAdmin->id,
            'opened_at' => now(),
            'opening_cash' => 5000.00,
            'cash_sales_amount' => 20000.00,
            'cash_refunds_amount' => 1000.00,
            'cash_in_hand_expected' => 24000.00,
            'closing_cash_counted' => 23700.00,
            'discrepancy_amount' => -300.00,
            'discrepancy_reason' => 'Cash change drawer shortage during peak Friday evening shift',
            'status' => 'closed',
            'closed_by' => $this->superAdmin->id,
            'closed_at' => now(),
        ]);

        $this->actingAs($this->accountant);

        $response = $this->get(route('reports.cash', ['store_id' => $this->store->id]));
        $response->assertOk();
        $response->assertSee('24,000.00');
        $response->assertSee('23,700.00');
        $response->assertSee('300.00');
    }
}
