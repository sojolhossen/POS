<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SupplierReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $warehouse;
    protected Supplier $supplier;
    protected User $superAdmin;
    protected User $storeManager;
    protected User $cashier;
    protected User $accountant;

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
            'name' => 'Beximco Apparels',
            'code' => 'SUP-BEX-01',
            'phone' => '01911223344',
            'is_active' => true,
            'opening_balance' => 0,
            'outstanding_balance' => 50000.00,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->storeManager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'is_active' => true,
        ]);

        $this->accountant = User::factory()->create([
            'role' => UserRole::ACCOUNTANT,
            'is_active' => true,
        ]);
    }

    /**
     * Test Cashier and Store Manager cannot access supplier creation or sensitive financial routes.
     */
    public function test_cashier_and_manager_cannot_create_suppliers(): void
    {
        // Cashier attempt
        $resCashier = $this->actingAs($this->cashier)->get(route('suppliers.create'));
        $resCashier->assertStatus(403);

        // Store Manager attempt
        $resManager = $this->actingAs($this->storeManager)->get(route('suppliers.create'));
        $resManager->assertStatus(403);

        // Super Admin succeeds
        $resAdmin = $this->actingAs($this->superAdmin)->get(route('suppliers.create'));
        $resAdmin->assertStatus(200);
    }

    /**
     * Test inactive supplier cannot be chosen for new purchase order.
     */
    public function test_inactive_supplier_cannot_be_selected_for_po(): void
    {
        $this->supplier->update(['is_active' => false]);

        $category = Category::create(['code' => 'KNT', 'name' => 'Knit', 'is_active' => true]);
        $product = Product::create(['name' => 'T-Shirt', 'sku' => 'TSHIRT-001', 'category_id' => $category->id, 'base_price' => 500, 'is_active' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Black',
            'barcode' => '894550010001',
            'purchase_price' => 200,
            'weighted_average_cost' => 200,
            'selling_price' => 500,
            'is_active' => true,
        ]);

        $purchaseService = app(PurchaseService::class);

        $this->expectException(\Exception::class);
        $purchaseService->createPurchaseOrder(
            $this->supplier,
            $this->warehouse,
            [['product_variant_id' => $variant->id, 'quantity' => 10, 'unit_cost' => 200]],
            0,
            $this->superAdmin
        );
    }

    /**
     * Test recording supplier payment decreases outstanding balance.
     */
    public function test_supplier_payment_recording_updates_balance(): void
    {
        $response = $this->actingAs($this->accountant)->post(route('suppliers.payments.store', $this->supplier), [
            'amount' => 20000.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => now()->format('Y-m-d'),
            'reference_number' => 'BEFTN-99120',
            'notes' => 'Part payment of monthly bill',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('suppliers.show', $this->supplier));
        $this->supplier->refresh();

        // 50,000 - 20,000 = 30,000
        $this->assertEquals(30000.00, (float) $this->supplier->outstanding_balance);
        $this->assertEquals(20000.00, (float) $this->supplier->total_paid);
    }

    /**
     * Test supplier return reduces stock and reduces supplier outstanding balance via credit note.
     */
    public function test_supplier_return_reduces_stock_and_offsets_liability(): void
    {
        $category = Category::create(['code' => 'DNM', 'name' => 'Denim', 'is_active' => true]);
        $product = Product::create(['name' => 'Jacket', 'sku' => 'JKT-001', 'category_id' => $category->id, 'base_price' => 3000, 'is_active' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'XL',
            'color' => 'Navy Blue',
            'barcode' => '894550020001',
            'purchase_price' => 1200,
            'weighted_average_cost' => 1200,
            'selling_price' => 3000,
            'is_active' => true,
        ]);

        StockBalance::create([
            'store_id' => $this->warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 15,
            'quantity_reserved' => 0,
            'damaged_quantity' => 5,
        ]);

        $supplierReturnService = app(SupplierReturnService::class);

        // Return 5 damaged jackets @ 1200 each = 6000 BDT credit note
        $ret = $supplierReturnService->processSupplierReturn(
            $this->supplier,
            $this->warehouse,
            [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 5,
                    'unit_cost' => 1200.00,
                    'condition' => 'damaged',
                    'reason' => 'Defective zipper',
                ]
            ],
            'credit_note',
            $this->superAdmin,
            'CN-BEX-001'
        );

        $this->assertNotNull($ret->id);
        $this->assertEquals(6000.00, (float) $ret->total_credit_amount);

        // Damaged stock should decrease from 5 to 0
        $bal = StockBalance::where('store_id', $this->warehouse->id)->where('product_variant_id', $variant->id)->first();
        $this->assertEquals(0, $bal->damaged_quantity);

        // Supplier outstanding balance reduced by 6000 (30,000 - 6,000 = 24,000)
        $this->supplier->refresh();
        $this->assertEquals(44000.00, (float) $this->supplier->outstanding_balance); // 50,000 initial - 6,000 = 44,000
    }
}
