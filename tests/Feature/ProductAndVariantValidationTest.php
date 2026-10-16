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
}
