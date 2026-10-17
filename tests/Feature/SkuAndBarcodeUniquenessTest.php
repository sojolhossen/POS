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

    /**
     * Test barcode scan lookup accurately returns stock across locations.
     */
    public function test_barcode_scan_lookup_returns_accurate_stock_for_super_admin(): void
    {
        $superAdmin = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::SUPER_ADMIN,
            'store_id' => null,
            'is_active' => true,
        ]);

        $storeA = \App\Models\Store::create(['name' => 'Central Warehouse', 'code' => 'CW-01', 'is_active' => true]);
        $storeB = \App\Models\Store::create(['name' => 'Gulshan Store', 'code' => 'GS-01', 'is_active' => true]);

        $category = Category::create(['name' => 'Shirts', 'code' => 'SH', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Oxford Shirt',
            'sku' => 'SH-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 500,
            'base_selling_price' => 1200,
            'is_active' => true,
        ]);

        $barcode = '8801122334455';
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Navy',
            'barcode' => $barcode,
            'selling_price' => 1200,
            'is_active' => true,
        ]);

        \App\Models\StockBalance::create([
            'store_id' => $storeA->id,
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 15,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        \App\Models\StockBalance::create([
            'store_id' => $storeB->id,
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        // Scenario 1: Super admin scans without store_id (Central Command) -> must return total 20, NOT 0!
        $response = $this->actingAs($superAdmin)->postJson(route('barcodes.scan-lookup'), [
            'barcode' => $barcode,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(20, $data['variant']['stock_on_hand']);
        $this->assertEquals(20, $data['variant']['total_stock_all_stores']);

        // Scenario 2: Super admin scans with store_id = storeA -> returns 15
        $responseA = $this->actingAs($superAdmin)->postJson(route('barcodes.scan-lookup'), [
            'barcode' => $barcode,
            'store_id' => $storeA->id,
        ]);

        $responseA->assertStatus(200);
        $dataA = $responseA->json();
        $this->assertEquals(15, $dataA['variant']['stock_on_hand']);
    }

    /**
     * Test barcode index filters by category and search keyword.
     */
    public function test_barcode_workstation_filters_by_category_and_keyword(): void
    {
        $superAdmin = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $catShirt = Category::create(['name' => 'Shirt Category', 'code' => 'SHT', 'is_active' => true]);
        $catPant = Category::create(['name' => 'Pant Category', 'code' => 'PNT', 'is_active' => true]);

        $prodShirt = Product::create([
            'name' => 'Formal Cotton Shirt',
            'sku' => 'SHT-001',
            'category_id' => $catShirt->id,
            'base_purchase_price' => 400,
            'base_selling_price' => 900,
            'is_active' => true,
        ]);

        $prodPant = Product::create([
            'name' => 'Denim Blue Jeans',
            'sku' => 'PNT-001',
            'category_id' => $catPant->id,
            'base_purchase_price' => 700,
            'base_selling_price' => 1500,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $prodShirt->id,
            'size' => 'M',
            'color' => 'White',
            'barcode' => '880000000101',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $prodPant->id,
            'size' => '32',
            'color' => 'Blue',
            'barcode' => '880000000202',
            'is_active' => true,
        ]);

        // Filter by Shirt Category
        $response = $this->actingAs($superAdmin)->get(route('barcodes.index', ['category_id' => $catShirt->id]));
        $response->assertStatus(200);
        $response->assertSee('Formal Cotton Shirt');
        $response->assertDontSee('Denim Blue Jeans');

        // Search by keyword "Denim"
        $searchResponse = $this->actingAs($superAdmin)->get(route('barcodes.index', ['search' => 'Denim']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Denim Blue Jeans');
        $searchResponse->assertDontSee('Formal Cotton Shirt');
    }

    /**
     * Test single variant and whole product quick printing.
     */
    public function test_quick_barcode_printing_for_single_variant_and_whole_product(): void
    {
        $superAdmin = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $cat = Category::create(['name' => 'Panjabi', 'code' => 'PJ', 'is_active' => true]);
        $prod = Product::create([
            'name' => 'Festive Panjabi',
            'sku' => 'PJ-001',
            'category_id' => $cat->id,
            'base_purchase_price' => 800,
            'base_selling_price' => 1800,
            'is_active' => true,
        ]);

        $v1 = ProductVariant::create([
            'product_id' => $prod->id,
            'size' => '40',
            'color' => 'Maroon',
            'barcode' => '880000001111',
            'is_active' => true,
        ]);

        $v2 = ProductVariant::create([
            'product_id' => $prod->id,
            'size' => '42',
            'color' => 'Maroon',
            'barcode' => '880000001112',
            'is_active' => true,
        ]);

        // Single variant quick print (3 copies)
        $respSingle = $this->actingAs($superAdmin)->post(route('barcodes.print'), [
            'single_variant_id' => $v1->id,
            'single_quantity' => 3,
        ]);
        $respSingle->assertStatus(200);
        $respSingle->assertViewHas('labels', function ($labels) {
            return count($labels) === 3;
        });

        // Whole product quick print
        $respProduct = $this->actingAs($superAdmin)->get(route('barcodes.print', [
            'product_id' => $prod->id,
        ]));
        $respProduct->assertStatus(200);
        $respProduct->assertViewHas('labels', function ($labels) {
            return count($labels) >= 2;
        });
    }
}

