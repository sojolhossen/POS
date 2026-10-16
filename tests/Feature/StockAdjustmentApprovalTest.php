<?php

namespace Tests\Feature;

use App\Enums\StockAdjustmentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test stock adjustment request does not touch stock until approved by Super Admin.
     */
    public function test_stock_adjustment_requires_super_admin_approval_before_altering_stock(): void
    {
        $store = Store::create(['name' => 'Uttara', 'code' => 'STR-002', 'is_active' => true]);
        $category = Category::create(['name' => 'Shirts', 'code' => 'SH', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Linen Shirt',
            'sku' => 'SH-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 600,
            'base_selling_price' => 1200,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'White',
            'barcode' => '8801234500003',
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $store->id,
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // Establish initial 10 units
        InventoryService::setOpeningStock(
            variantId: $variant->id,
            storeId: $store->id,
            quantity: 10,
            notes: 'Initial 10 units',
            user: $superAdmin
        );

        // 1. Manager requests an adjustment proposing 14 units (found 4 extra units on rack)
        $adjustment = InventoryService::requestAdjustment(
            storeId: $store->id,
            variantId: $variant->id,
            proposedQuantity: 14,
            reason: 'Found 4 unboxed units in backroom during audit',
            requester: $manager
        );

        $this->assertEquals(StockAdjustmentStatus::PENDING, $adjustment->status);

        // Crucial Check: Balance must STILL be 10! Not 14!
        $balance = StockBalance::where('store_id', $store->id)->where('product_variant_id', $variant->id)->first();
        $this->assertEquals(10, $balance->quantity_on_hand);

        // 2. Non-admin manager attempts to approve adjustment -> AuthorizationException
        $this->expectException(AuthorizationException::class);
        InventoryService::approveAdjustment($adjustment, $manager);

        // Reset exception expectation for remaining test steps
    }

    /**
     * Test Super Admin approval applies ledger movement and updates balance.
     */
    public function test_super_admin_approval_updates_balance_and_ledger(): void
    {
        $store = Store::create(['name' => 'Uttara', 'code' => 'STR-002', 'is_active' => true]);
        $category = Category::create(['name' => 'Shirts', 'code' => 'SH', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Linen Shirt',
            'sku' => 'SH-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 600,
            'base_selling_price' => 1200,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'White',
            'barcode' => '8801234500004',
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        InventoryService::setOpeningStock(
            variantId: $variant->id,
            storeId: $store->id,
            quantity: 10,
            notes: 'Initial 10 units',
            user: $superAdmin
        );

        $adjustment = InventoryService::requestAdjustment(
            storeId: $store->id,
            variantId: $variant->id,
            proposedQuantity: 14,
            reason: 'Physical count verified surplus',
            requester: $superAdmin
        );

        // Super Admin approves
        InventoryService::approveAdjustment($adjustment, $superAdmin);

        $adjustment->refresh();
        $this->assertEquals(StockAdjustmentStatus::APPROVED, $adjustment->status);
        $this->assertEquals($superAdmin->id, $adjustment->approved_by);

        // Balance must now be updated to 14
        $balance = StockBalance::where('store_id', $store->id)->where('product_variant_id', $variant->id)->first();
        $this->assertEquals(14, $balance->quantity_on_hand);

        // Verify ledger movement created with delta of +4
        $ledger = StockLedger::where('reference_number', "ADJ-{$adjustment->id}")->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(4, $ledger->quantity);
        $this->assertEquals(14, $ledger->balance_after);
    }
}
