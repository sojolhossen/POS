<?php

namespace Tests\Feature;

use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrentStockOversellingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test overselling is strictly blocked: requesting more than available throws exception.
     */
    public function test_overselling_is_blocked_when_insufficient_stock_available(): void
    {
        $store = Store::create(['name' => 'Dhanmondi', 'code' => 'STR-001', 'is_active' => true]);
        $category = Category::create(['name' => 'Shirts', 'code' => 'SH', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Cotton Shirt',
            'sku' => 'SH-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 500,
            'base_selling_price' => 1000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Blue',
            'barcode' => '8801234500001',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['role' => UserRole::STORE_MANAGER, 'is_active' => true]);

        // Establish 5 units on hand
        InventoryService::setOpeningStock(
            variantId: $variant->id,
            storeId: $store->id,
            quantity: 5,
            notes: 'Initial 5 units',
            user: $user
        );

        $balance = StockBalance::where('store_id', $store->id)->where('product_variant_id', $variant->id)->first();
        $this->assertEquals(5, $balance->quantity_on_hand);

        // Attempting to deduct 6 units (more than 5 available) must throw DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Insufficient stock');

        InventoryService::recordStockMovement(
            type: StockTransactionType::SALE,
            variantId: $variant->id,
            storeId: $store->id,
            quantity: -6,
            notes: 'Attempted overselling 6 units',
            actor: $user
        );

        // Verify balance remained at 5
        $this->assertEquals(5, $balance->fresh()->quantity_on_hand);
    }
}
