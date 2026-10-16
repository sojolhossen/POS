<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BarcodeService;
use App\Services\SkuGeneratorService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuAndBarcodeUniquenessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test SKU generation creates sequential unique SKUs with category prefix.
     */
    public function test_sku_generation_produces_unique_sequential_skus(): void
    {
        $category = Category::create([
            'name' => 'Shirts',
            'code' => 'SH',
            'is_active' => true,
        ]);

        $sku1 = SkuGeneratorService::generate($category);
        $this->assertEquals('SH-000001', $sku1);

        Product::create([
            'name' => 'Shirt 1',
            'sku' => $sku1,
            'category_id' => $category->id,
            'base_purchase_price' => 500,
            'base_selling_price' => 1000,
            'is_active' => true,
        ]);

        $sku2 = SkuGeneratorService::generate($category);
        $this->assertEquals('SH-000002', $sku2);

        $this->assertNotEquals($sku1, $sku2);
    }

    /**
     * Test unique barcode constraint prevents duplicate barcodes across variants.
     */
    public function test_unique_barcode_constraint_prevents_duplicate_barcode_assignment(): void
    {
        $category = Category::create([
            'name' => 'Panjabi',
            'code' => 'PJ',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Panjabi Style',
            'sku' => 'PJ-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 1000,
            'base_selling_price' => 2000,
            'is_active' => true,
        ]);

        $barcode = '8809999999991';

        ProductVariant::create([
            'product_id' => $product->id,
            'size' => '40',
            'color' => 'White',
            'barcode' => $barcode,
            'is_active' => true,
        ]);

        // Attempting to create second variant with identical barcode must fail
        $this->expectException(QueryException::class);

        ProductVariant::create([
            'product_id' => $product->id,
            'size' => '42',
            'color' => 'White',
            'barcode' => $barcode,
            'is_active' => true,
        ]);
    }

    /**
     * Test barcode reprinting retains the original barcode.
     */
    public function test_barcode_reprinting_retains_original_barcode(): void
    {
        $category = Category::create(['name' => 'Pants', 'code' => 'PT', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Chino Pant',
            'sku' => 'PT-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 800,
            'base_selling_price' => 1600,
            'is_active' => true,
        ]);

        $initialBarcode = BarcodeService::generateUniqueBarcode();

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => '32',
            'color' => 'Khaki',
            'barcode' => $initialBarcode,
            'is_active' => true,
        ]);

        // Simulate re-printing label
        $retrievedVariant = ProductVariant::findOrFail($variant->id);
        $this->assertEquals($initialBarcode, $retrievedVariant->barcode);

        // Barcode svg generates without modifying the stored barcode
        $svg = BarcodeService::renderSvg($retrievedVariant->barcode);
        $this->assertNotEmpty($svg);
        $this->assertEquals($initialBarcode, $variant->fresh()->barcode);
    }
}
