<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderAndReceivingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $warehouse;
    protected Supplier $supplier;
    protected ProductVariant $variant;
    protected User $superAdmin;
    protected User $warehouseStaff;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Store::create([
            'name' => 'Central Warehouse',
            'code' => 'CW-01',
            'is_warehouse' => true,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Apex Knitwear Ltd',
            'code' => 'SUP-0001',
            'phone' => '01711223344',
            'is_active' => true,
            'opening_balance' => 0,
            'outstanding_balance' => 0,
        ]);

        $category = Category::create([
            'code' => 'MEN',
            'name' => 'Men Fashion',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Premium Oxford Shirt',
            'sku' => 'POS-SHIRT-001',
            'category_id' => $category->id,
            'base_price' => 1200.00,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Navy Blue',
            'size' => 'L',
            'sku' => 'POS-SHIRT-001-BLU-L',
            'barcode' => '894110010001',
            'purchase_price' => 400.00,
            'weighted_average_cost' => 400.00,
            'selling_price' => 1200.00,
            'is_active' => true,
        ]);

        StockBalance::create([
            'store_id' => $this->warehouse->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->warehouseStaff = User::factory()->create([
            'role' => UserRole::WAREHOUSE_STAFF,
            'store_id' => $this->warehouse->id,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'is_active' => true,
        ]);
    }

    /**
     * Test PO creation sets status to submitted and requires Super Admin approval.
     */
    public function test_po_requires_super_admin_approval_before_receiving(): void
    {
        $purchaseService = app(PurchaseService::class);

        $items = [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 100,
                'unit_cost' => 450.00,
            ]
        ];

        $po = $purchaseService->createPurchaseOrder(
            $this->supplier,
            $this->warehouse,
            $items,
            500.00, // additional transport costs
            $this->warehouseStaff,
            'Initial batch order'
        );

        $this->assertEquals('submitted', $po->status);
        $this->assertFalse($po->isApproved());

        // Attempt receiving an unapproved order should fail
        $this->expectException(\Exception::class);
        $purchaseService->receiveStock(
            $po,
            $this->warehouse,
            [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity_received' => 100,
                    'quantity_accepted' => 100,
                    'quantity_damaged' => 0,
                    'unit_cost' => 450.00,
                ]
            ],
            500.00,
            $this->warehouseStaff
        );
    }

    /**
     * Test Super Admin approves PO and partial receiving updates stock & WAC.
     */
    public function test_super_admin_approval_and_partial_receiving_with_wac_calculation(): void
    {
        $purchaseService = app(PurchaseService::class);

        $items = [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 100,
                'unit_cost' => 450.00,
            ]
        ];

        $po = $purchaseService->createPurchaseOrder(
            $this->supplier,
            $this->warehouse,
            $items,
            1000.00, // additional freight
            $this->warehouseStaff
        );

        // Super Admin approves the PO
        $purchaseService->approvePurchaseOrder($po, $this->superAdmin);
        $po->refresh();
        $this->assertEquals('approved', $po->status);
        $this->assertTrue($po->isApproved());

        // Session 1: Partial receiving of 60 units (55 accepted, 5 damaged)
        $receivingSession1 = $purchaseService->receiveStock(
            $po,
            $this->warehouse,
            [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity_received' => 60,
                    'quantity_accepted' => 55,
                    'quantity_damaged' => 5,
                    'unit_cost' => 450.00,
                ]
            ],
            600.00, // session freight
            $this->warehouseStaff,
            'CHALLAN-001'
        );

        $po->refresh();
        $this->assertEquals('partially_received', $po->status);
        $this->assertEquals(60, $po->total_received_qty);

        // Verify sellable stock only increased by accepted quantity (50 initial + 55 = 105)
        $balance = StockBalance::where('store_id', $this->warehouse->id)
            ->where('product_variant_id', $this->variant->id)
            ->first();

        $this->assertEquals(105, $balance->quantity_on_hand);
        $this->assertEquals(5, $balance->damaged_quantity);

        // Verify supplier liability increased by accepted items value
        $this->supplier->refresh();
        $expectedBill = 55 * 450.00;
        $this->assertEquals($expectedBill, (float) $this->supplier->outstanding_balance);

        // Verify WAC updated on product variant
        $this->variant->refresh();
        $this->assertGreaterThan(400.00, (float) $this->variant->weighted_average_cost);

        // Session 2: Receive remaining 40 units (all 40 accepted)
        $receivingSession2 = $purchaseService->receiveStock(
            $po,
            $this->warehouse,
            [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity_received' => 40,
                    'quantity_accepted' => 40,
                    'quantity_damaged' => 0,
                    'unit_cost' => 450.00,
                ]
            ],
            400.00,
            $this->warehouseStaff,
            'CHALLAN-002'
        );

        $po->refresh();
        $this->assertEquals('completed', $po->status);
        $this->assertEquals(100, $po->total_received_qty);

        $balance->refresh();
        $this->assertEquals(145, $balance->quantity_on_hand); // 105 + 40
    }

    /**
     * Test unauthorized user cannot approve purchase order via route.
     */
    public function test_non_super_admin_cannot_approve_po(): void
    {
        $purchaseService = app(PurchaseService::class);
        $po = $purchaseService->createPurchaseOrder(
            $this->supplier,
            $this->warehouse,
            [['product_variant_id' => $this->variant->id, 'quantity' => 50, 'unit_cost' => 400.00]],
            0,
            $this->warehouseStaff
        );

        $response = $this->actingAs($this->cashier)->post(route('purchase-orders.approve', $po));
        $response->assertStatus(403);

        $po->refresh();
        $this->assertEquals('submitted', $po->status);
    }
}
