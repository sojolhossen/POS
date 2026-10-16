<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionRollbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test atomic transaction rollback on failure prevents partial database updates.
     */
    public function test_transaction_rollback_prevents_partial_updates(): void
    {
        $store = Store::create(['name' => 'Mirpur', 'code' => 'STR-003', 'is_active' => true]);
        $category = Category::create(['name' => 'Pants', 'code' => 'PT', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Formal Pant',
            'sku' => 'PT-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 700,
            'base_selling_price' => 1400,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => '32',
            'color' => 'Black',
            'barcode' => '8801234500002',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        // Attempt a multi-step operation that deliberately throws an unhandled exception halfway
        try {
            DB::transaction(function () use ($variant, $store, $user) {
                // Step 1: Record stock movement
                InventoryService::setOpeningStock(
                    variantId: $variant->id,
                    storeId: $store->id,
                    quantity: 20,
                    notes: 'Step 1 of transaction',
                    user: $user
                );

                // Step 2: Deliberate failure simulating network or validation crash
                throw new Exception('Simulated fatal transaction failure');
            });
        } catch (Exception $e) {
            // Expected
        }

        // Entire transaction must have rolled back cleanly
        $balance = StockBalance::where('store_id', $store->id)->where('product_variant_id', $variant->id)->first();
        $this->assertNull($balance);
        $this->assertDatabaseMissing('stock_ledgers', [
            'store_id' => $store->id,
            'product_variant_id' => $variant->id,
        ]);
    }
}
