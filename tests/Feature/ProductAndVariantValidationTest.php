<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductAndVariantValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unique composite constraint prevents duplicate variants on same product.
     */
    public function test_unique_composite_constraint_prevents_duplicate_variants(): void
    {
        $category = Category::create([
            'name' => 'Shirts',
            'code' => 'SH',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Classic Cotton Shirt',
            'sku' => 'SH-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 500.00,
            'base_selling_price' => 1000.00,
            'is_active' => true,
        ]);

        // First variant
        ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Navy Blue',
            'barcode' => '8801234567891',
            'is_active' => true,
        ]);

        // Expect exception when attempting duplicate (product_id, size, color)
        $this->expectException(QueryException::class);

        ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Navy Blue',
            'barcode' => '8801234567892',
            'is_active' => true,
        ]);
    }

    /**
     * Test product photo column does not exist in products or product_variants tables (rule compliance).
     */
    public function test_product_photo_columns_do_not_exist(): void
    {
        $this->assertFalse(Schema::hasColumn('products', 'photo'));
        $this->assertFalse(Schema::hasColumn('products', 'image'));
        $this->assertFalse(Schema::hasColumn('product_variants', 'photo'));
        $this->assertFalse(Schema::hasColumn('product_variants', 'image'));
    }

    /**
     * Test product creation form validation.
     */
    public function test_product_creation_requires_valid_data(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => '', // missing name
            'category_id' => 999999, // non-existent category
            'base_purchase_price' => -10, // negative price
            'base_selling_price' => -20,
            'variants' => [], // missing variants
        ]);

        $response->assertSessionHasErrors(['name', 'category_id', 'base_purchase_price', 'base_selling_price', 'variants']);
    }

    /**
     * Test product creation with variant quantities creates stock balances.
     */
    public function test_product_creation_records_initial_stock_for_variants(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $store = \App\Models\Store::create([
            'name' => 'Central Warehouse',
            'code' => 'WH-01',
            'is_warehouse' => true,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Pants',
            'code' => 'PT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Chino Pants',
            'category_id' => $category->id,
            'store_id' => $store->id,
            'base_purchase_price' => 800.00,
            'base_selling_price' => 1500.00,
            'variants' => [
                ['color' => 'Beige', 'size' => '32', 'quantity' => 15, 'selling_price' => ''],
                ['color' => 'Navy', 'size' => '34', 'quantity' => 20, 'selling_price' => ''],
            ],
        ]);

        $response->assertRedirect('/products');

        $variant1 = ProductVariant::where('color', 'Beige')->where('size', '32')->first();
        $this->assertNotNull($variant1);

        $balance1 = \App\Models\StockBalance::where('product_variant_id', $variant1->id)
            ->where('store_id', $store->id)
            ->first();
        $this->assertNotNull($balance1);
        $this->assertEquals(15, $balance1->quantity_on_hand);

        $variant2 = ProductVariant::where('color', 'Navy')->where('size', '34')->first();
        $this->assertNotNull($variant2);

        $balance2 = \App\Models\StockBalance::where('product_variant_id', $variant2->id)
            ->where('store_id', $store->id)
            ->first();
        $this->assertNotNull($balance2);
        $this->assertEquals(20, $balance2->quantity_on_hand);
    }
}
