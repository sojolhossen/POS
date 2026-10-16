<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Store $warehouse;
    protected Store $outletStore;
    protected ProductVariant $variant;
    protected User $superAdmin;
    protected User $storeManager;
    protected User $warehouseStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Store::create([
            'name' => 'Central Warehouse',
            'code' => 'CW-01',
            'is_warehouse' => true,
            'is_active' => true,
        ]);

        $this->outletStore = Store::create([
            'name' => 'Dhanmondi Outlet',
            'code' => 'DHM-01',
            'is_warehouse' => false,
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'PAN',
            'name' => 'Panjabi Collection',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Semi-fitted Silk Panjabi',
            'sku' => 'PANJABI-001',
            'category_id' => $category->id,
            'base_price' => 2500.00,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'White',
            'size' => '42',
            'sku' => 'PANJABI-001-WHT-42',
            'barcode' => '894220010042',
            'cost_price' => 1000.00,
            'weighted_average_cost' => 1000.00,
            'selling_price' => 2500.00,
            'is_active' => true,
        ]);

        // Source Warehouse has 100 pcs
        StockBalance::create([
            'store_id' => $this->warehouse->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        // Destination Outlet has 0 pcs
        StockBalance::create([
            'store_id' => $this->outletStore->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->storeManager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $this->outletStore->id,
            'is_active' => true,
        ]);

        $this->warehouseStaff = User::factory()->create([
            'role' => UserRole::WAREHOUSE_STAFF,
            'store_id' => $this->warehouse->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test full transfer workflow: Request -> Approve -> Dispatch -> In Transit -> Confirmed Receipt.
     */
    public function test_complete_transfer_lifecycle_and_in_transit_isolation(): void
    {
        $transferService = app(TransferService::class);

        // 1. Store Manager requests 30 pcs from Central Warehouse
        $transfer = $transferService->requestTransfer(
            $this->warehouse,
            $this->outletStore,
            [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 30,
                ]
            ],
            $this->storeManager,
            'Festival stock replenishment'
        );

        $this->assertEquals('requested', $transfer->status);
        $this->assertNotNull($transfer->reference_number);

        // Stocks should NOT have changed yet
        $warehouseBal = StockBalance::where('store_id', $this->warehouse->id)->where('product_variant_id', $this->variant->id)->first();
        $outletBal = StockBalance::where('store_id', $this->outletStore->id)->where('product_variant_id', $this->variant->id)->first();
        $this->assertEquals(100, $warehouseBal->quantity_on_hand);
        $this->assertEquals(0, $outletBal->quantity_on_hand);

        // 2. Manager / Super Admin approves transfer
        $transferService->approveTransfer($transfer, $this->superAdmin);
        $transfer->refresh();
        $this->assertEquals('approved', $transfer->status);

        // 3. Warehouse Staff dispatches 30 pcs
        $transferItem = $transfer->items->first();
        $transferService->dispatchTransfer(
            $transfer,
            [$transferItem->id => 30],
            $this->warehouseStaff
        );

        $transfer->refresh();
        $this->assertEquals('in_transit', $transfer->status);

        // CRITICAL CHECK: Source stock MUST be reduced immediately (100 - 30 = 70)
        $warehouseBal->refresh();
        $outletBal->refresh();
        $this->assertEquals(70, $warehouseBal->quantity_on_hand);
        // Destination stock MUST NOT be increased yet while in transit!
        $this->assertEquals(0, $outletBal->quantity_on_hand);

        // 4. Outlet receives stock: 28 accepted, 2 damaged in transit with discrepancy note
        $transferService->receiveTransfer(
            $transfer,
            [$transferItem->id => 28],
            $this->storeManager,
            [$transferItem->id => 2],
            [$transferItem->id => '2 Panjabis torn during courier transit']
        );

        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);

        // Destination stock updated: 28 accepted to sellable stock, 2 to damaged stock
        $outletBal->refresh();
        $this->assertEquals(28, $outletBal->quantity_on_hand);
        $this->assertEquals(2, $outletBal->damaged_quantity);

        // Total inventory across both stores: 70 + 28 = 98 sellable + 2 damaged = 100 total (conserved)
        $this->assertEquals(70, $warehouseBal->quantity_on_hand);
        $this->assertEquals(100, $warehouseBal->quantity_on_hand + $outletBal->quantity_on_hand + $outletBal->damaged_quantity);
    }

    /**
     * Test cannot dispatch more than available warehouse stock.
     */
    public function test_cannot_dispatch_more_than_available_source_stock(): void
    {
        $transferService = app(TransferService::class);

        $transfer = $transferService->requestTransfer(
            $this->warehouse,
            $this->outletStore,
            [['product_variant_id' => $this->variant->id, 'quantity' => 200]], // warehouse only has 100
            $this->storeManager
        );

        $transferService->approveTransfer($transfer, $this->superAdmin);
        $item = $transfer->items->first();

        $this->expectException(\Exception::class);
        $transferService->dispatchTransfer(
            $transfer,
            [$item->id => 200],
            $this->warehouseStaff
        );
    }
}
