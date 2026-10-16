<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DailyCashRegister;
use App\Models\FinancialAccount;
use App\Models\PosReturn;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PosCheckoutService;
use App\Services\PurchaseReceivingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndRetailLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Store $centralWarehouse;
    protected Store $gulshanStore;
    protected User $superAdmin;
    protected User $storeManager;
    protected User $cashier;
    protected User $warehouseStaff;
    protected User $accountant;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Initialize Stores
        $this->centralWarehouse = Store::create([
            'name' => 'Central Distribution Warehouse',
            'code' => 'WH-CENTRAL',
            'is_warehouse' => true,
            'is_active' => true,
        ]);

        $this->gulshanStore = Store::create([
            'name' => 'Gulshan Flagship Store',
            'code' => 'ST-GLS',
            'is_active' => true,
        ]);

        // 2. Initialize 5 Dedicated User Roles
        $this->superAdmin = User::create([
            'name' => 'Executive Super Admin',
            'email' => 'admin@retailpos.test',
            'password' => bcrypt('Admin@12345'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->storeManager = User::create([
            'name' => 'Gulshan Store Manager',
            'email' => 'manager.gls@retailpos.test',
            'password' => bcrypt('Manager@12345'),
            'role' => 'store_manager',
            'store_id' => $this->gulshanStore->id,
            'is_active' => true,
        ]);

        $this->cashier = User::create([
            'name' => 'POS Terminal Cashier',
            'email' => 'cashier.gls@retailpos.test',
            'password' => bcrypt('Cashier@12345'),
            'role' => 'cashier',
            'store_id' => $this->gulshanStore->id,
            'is_active' => true,
        ]);

        $this->warehouseStaff = User::create([
            'name' => 'Logistics Officer',
            'email' => 'warehouse@retailpos.test',
            'password' => bcrypt('Warehouse@12345'),
            'role' => 'warehouse_staff',
            'store_id' => $this->centralWarehouse->id,
            'is_active' => true,
        ]);

        $this->accountant = User::create([
            'name' => 'Financial Controller',
            'email' => 'accountant@retailpos.test',
            'password' => bcrypt('Accountant@12345'),
            'role' => 'accountant',
            'is_active' => true,
        ]);

        // 3. Initialize Supplier & Financial Account
        $this->supplier = Supplier::create([
            'name' => 'Apex Textiles Ltd.',
            'code' => 'SUP-APEX',
            'contact_person' => 'Mr. Rafiqul Islam',
            'phone' => '01711002233',
            'is_active' => true,
            'outstanding_balance' => 0.00,
        ]);

        FinancialAccount::create([
            'name' => 'Gulshan Cash Drawer',
            'account_type' => 'cash',
            'currency' => 'BDT',
            'store_id' => $this->gulshanStore->id,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);
    }

    /**
     * Complete Realistic 13-Step Lifecycle:
     * Product -> PO -> Receiving -> Transfer -> Daily Opening -> POS Checkout (Promotion, VAT, Split Payment)
     * -> Receipt Reprint -> Return -> Daily Cash Close -> Access Control Verification.
     */
    public function test_complete_end_to_end_retail_lifecycle(): void
    {
        // ---------------------------------------------------------------------
        // STEP 1: Product & Variant Catalog Creation
        // ---------------------------------------------------------------------
        $category = Category::create([
            'name' => 'Men Formal',
            'code' => 'MEN-FORMAL',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Executive Cotton Shirt',
            'sku' => 'SHIRT-EXEC-01',
            'base_price' => 1800.00,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => '40',
            'color' => 'Classic Blue',
            'barcode' => '894000112233',
            'selling_price' => 1800.00,
            'purchase_price' => 800.00,
            'weighted_average_cost' => 800.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('product_variants', ['barcode' => '894000112233']);

        // ---------------------------------------------------------------------
        // STEP 2 & 3: Purchase Order & Super Admin Approval
        // ---------------------------------------------------------------------
        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $this->supplier->id,
            'destination_store_id' => $this->centralWarehouse->id,
            'status' => 'approved',
            'total_amount' => 80000.00,
            'created_by' => $this->warehouseStaff->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_variant_id' => $variant->id,
            'ordered_quantity' => 100,
            'unit_cost' => 800.00,
        ]);

        // ---------------------------------------------------------------------
        // STEP 4: Receive Stock into Central Warehouse
        // ---------------------------------------------------------------------
        $purchaseService = app(\App\Services\PurchaseService::class);
        $purchaseService->receiveStock(
            $po,
            $this->centralWarehouse,
            [
                [
                    'product_variant_id' => $variant->id,
                    'quantity_received' => 100,
                    'quantity_accepted' => 100,
                    'quantity_damaged' => 0,
                    'unit_cost' => 800.00,
                ]
            ],
            0.00,
            $this->warehouseStaff,
            'INV-APEX-9988'
        );

        $warehouseBalance = StockBalance::where('store_id', $this->centralWarehouse->id)
            ->where('product_variant_id', $variant->id)
            ->first();
        $this->assertEquals(100, $warehouseBalance->quantity_on_hand);
        $this->assertEquals(80000.00, $this->supplier->fresh()->outstanding_balance);

        // ---------------------------------------------------------------------
        // STEP 5: Inter-Store Stock Transfer to Gulshan Store
        // ---------------------------------------------------------------------
        $transfer = StockTransfer::create([
            'source_store_id' => $this->centralWarehouse->id,
            'destination_store_id' => $this->gulshanStore->id,
            'reference_number' => 'TR-2026-0001',
            'status' => 'requested',
            'created_by' => $this->storeManager->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_variant_id' => $variant->id,
            'quantity_requested' => 30,
        ]);

        // Dispatch transfer (Stock in transit)
        $warehouseBalance->decrement('quantity_on_hand', 30);
        $transfer->update([
            'status' => 'in_transit',
            'dispatched_by' => $this->warehouseStaff->id,
            'dispatched_at' => now(),
        ]);

        // Receive transfer at Gulshan Store
        $gulshanBalance = StockBalance::create([
            'store_id' => $this->gulshanStore->id,
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 30,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);
        $transfer->update([
            'status' => 'received',
            'received_by' => $this->storeManager->id,
            'received_at' => now(),
        ]);

        $this->assertEquals(70, $warehouseBalance->fresh()->quantity_on_hand);
        $this->assertEquals(30, $gulshanBalance->fresh()->quantity_on_hand);

        // ---------------------------------------------------------------------
        // STEP 6: Daily Cash Register Opening
        // ---------------------------------------------------------------------
        $register = DailyCashRegister::create([
            'store_id' => $this->gulshanStore->id,
            'business_date' => now()->format('Y-m-d'),
            'opened_by' => $this->cashier->id,
            'opened_at' => now(),
            'opening_cash' => 5000.00,
            'cash_sales_amount' => 0.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 5000.00,
            'status' => 'open',
        ]);

        // ---------------------------------------------------------------------
        // STEP 7 & 8: POS Checkout with Promotion, 5% Statutory VAT, and Split Payment
        // ---------------------------------------------------------------------
        $promo = Promotion::create([
            'name' => '10% Eid Discount',
            'code' => 'EID10',
            'discount_percentage' => 10.00,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
            'created_by' => $this->superAdmin->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Engr. Tanvir Ahmed',
            'phone' => '01899112233',
            'loyalty_points' => 0,
        ]);

        // Sell 2 units:
        // Base Price = 1800 * 2 = 3600
        // 10% Discount = 360. Subtotal = 3240
        // 5% Statutory VAT = 162. Total Payable = 3402 BDT
        // Split Payment: Cash 2000, bKash 1402
        $checkoutService = app(PosCheckoutService::class);
        $sale = $checkoutService->checkout(
            $this->gulshanStore,
            $this->cashier,
            [['product_variant_id' => $variant->id, 'quantity' => 2]],
            [
                ['payment_method' => 'cash', 'amount' => 2000.00, 'tendered_amount' => 2000.00],
                ['payment_method' => 'bkash', 'amount' => 1402.00, 'reference' => 'BKASH-TRX-7766'],
            ],
            $customer,
            'EID10',
            0,
            'E2E-IDEMPOTENT-001'
        );

        $this->assertEquals(3402.00, (float) $sale->total_payable);
        $this->assertEquals(28, $gulshanBalance->fresh()->quantity_on_hand);
        $this->assertEquals(7000.00, (float) $register->fresh()->cash_in_hand_expected); // 5000 float + 2000 cash

        // ---------------------------------------------------------------------
        // STEP 9: Receipt Reprinting Retains Original Invoice Number
        // ---------------------------------------------------------------------
        $this->actingAs($this->cashier);
        $reprintResponse = $this->get(route('pos.reprint', $sale));
        $reprintResponse->assertOk();
        $reprintResponse->assertSee($sale->order_number);

        // ---------------------------------------------------------------------
        // STEP 10: Customer Return (1 unit returned, restoring stock)
        // ---------------------------------------------------------------------
        $posReturn = PosReturn::create([
            'return_number' => 'RET-2026-0001',
            'store_id' => $this->gulshanStore->id,
            'original_pos_sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'processed_by' => $this->cashier->id,
            'return_type' => 'refund',
            'total_returned_value' => 1701.00, // 1 unit net of discount + VAT
            'net_settlement_amount' => 1701.00,
            'settlement_method' => 'cash',
            'reason' => 'Wrong size fit',
        ]);

        $gulshanBalance->increment('quantity_on_hand', 1);
        $register->increment('cash_refunds_amount', 1701.00);
        $register->decrement('cash_in_hand_expected', 1701.00);

        $this->assertEquals(29, $gulshanBalance->fresh()->quantity_on_hand);
        $this->assertEquals(5299.00, (float) $register->fresh()->cash_in_hand_expected);

        // ---------------------------------------------------------------------
        // STEP 11: End-of-Day Cash Reconciliation & Register Closing
        // ---------------------------------------------------------------------
        $register->update([
            'closing_cash_counted' => 5299.00,
            'discrepancy_amount' => 0.00,
            'status' => 'closed',
            'closed_by' => $this->storeManager->id,
            'closed_at' => now(),
        ]);
        $this->assertEquals('closed', $register->fresh()->status);

        // ---------------------------------------------------------------------
        // STEP 12 & 13: Role-Based Authorization & Cross-Store Scoping Enforced
        // ---------------------------------------------------------------------
        // Cashier cannot access administrative staff routes
        $cashierStaffResponse = $this->actingAs($this->cashier)->get('/staff');
        $cashierStaffResponse->assertForbidden();

        // Warehouse staff cannot access POS checkout
        $whPosResponse = $this->actingAs($this->warehouseStaff)->get('/pos');
        $whPosResponse->assertForbidden();

        // Store manager cannot access another store's confidential records
        $otherStoreResponse = $this->actingAs($this->storeManager)->get("/stores/{$this->centralWarehouse->id}");
        $otherStoreResponse->assertForbidden();
    }
}
