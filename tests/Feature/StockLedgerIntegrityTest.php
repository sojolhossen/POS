<?php

namespace Tests\Feature;

use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLedgerIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test stock balance strictly equals the cumulative ledger entries.
     */
    public function test_stock_balance_matches_cumulative_ledger_movements(): void
    {
        $warehouse = Store::create([
            'name' => 'Central Warehouse',
            'code' => 'WH-001',
            'is_warehouse' => true,
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Polo', 'code' => 'TS', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Pique Polo',
            'sku' => 'TS-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 500,
            'base_selling_price' => 1200,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Navy',
            'barcode' => '8801122334455',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // 1. Initial Opening Balance: +50
        InventoryService::setOpeningStock(
            variantId: $variant->id,
            storeId: $warehouse->id,
            quantity: 50,
            unitCost: 500.00,
            notes: 'Initial migration stock',
            user: $user
        );

        // 2. Warehouse Intake: +30
        InventoryService::receiveIntoWarehouse(
            variantId: $variant->id,
            warehouseId: $warehouse->id,
            quantity: 30,
            unitCost: 500.00,
            referenceNumber: 'REC-001',
            notes: 'Batch intake',
            user: $user
        );

        // 3. Outflow Movement (e.g. transfer dispatch or sample issue): -15
        InventoryService::recordStockMovement(
            type: StockTransactionType::TRANSFER_DISPATCH,
            variantId: $variant->id,
            storeId: $warehouse->id,
            quantity: -15,
            relatedStoreId: null,
            unitCost: 500.00,
            referenceNumber: 'TRF-001',
            notes: 'Dispatch to retail outlet',
            actor: $user
        );

        // Verify Balance
        $balance = StockBalance::where('store_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        $this->assertEquals(65, $balance->quantity_on_hand); // 50 + 30 - 15 = 65

        // Verify cumulative ledger sum
        $ledgerSum = StockLedger::where('store_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->sum('quantity');

        $this->assertEquals($balance->quantity_on_hand, $ledgerSum);

        // Verify ledger count
        $this->assertEquals(3, StockLedger::where('store_id', $warehouse->id)->count());
    }
}
