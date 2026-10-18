<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\DailyCashRegister;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\User;
use App\Services\PosCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSpecificPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashier;
    protected Store $store;
    protected Product $productA;
    protected Product $productB;
    protected ProductVariant $variantA;
    protected ProductVariant $variantB;
    protected DailyCashRegister $register;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Uttara Retail Store',
            'code' => 'STR-001',
            'is_warehouse' => false,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Clothing',
            'code' => 'CLOTH',
        ]);

        $this->productA = Product::create([
            'name' => 'Premium Panjabi',
            'sku' => 'PANJABI-001',
            'category_id' => $category->id,
            'base_purchase_price' => 1000.00,
            'base_selling_price' => 2000.00,
            'is_active' => true,
        ]);

        $this->variantA = ProductVariant::create([
            'product_id' => $this->productA->id,
            'barcode' => '880100000001',
            'size' => 'L',
            'color' => 'White',
            'selling_price' => 2000.00,
            'purchase_price' => 1000.00,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'name' => 'Casual Jeans',
            'sku' => 'JEANS-001',
            'category_id' => $category->id,
            'base_purchase_price' => 800.00,
            'base_selling_price' => 1500.00,
            'is_active' => true,
        ]);

        $this->variantB = ProductVariant::create([
            'product_id' => $this->productB->id,
            'barcode' => '880100000002',
            'size' => '32',
            'color' => 'Blue',
            'selling_price' => 1500.00,
            'purchase_price' => 800.00,
            'is_active' => true,
        ]);

        StockBalance::create([
            'store_id' => $this->store->id,
            'product_variant_id' => $this->variantA->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        StockBalance::create([
            'store_id' => $this->store->id,
            'product_variant_id' => $this->variantB->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        $this->register = DailyCashRegister::create([
            'store_id' => $this->store->id,
            'opened_by' => $this->cashier->id,
            'business_date' => now()->format('Y-m-d'),
            'opening_cash' => 5000.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    public function test_super_admin_can_create_and_edit_product_specific_promotion(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('promotions.store'), [
            'name' => 'Panjabi Special Discount',
            'code' => 'PANJABI20',
            'discount_percentage' => 20.00,
            'product_id' => $this->productA->id,
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'is_active' => true,
        ]);

        $response->assertRedirect(route('promotions.index'));
        $this->assertDatabaseHas('promotions', [
            'code' => 'PANJABI20',
            'product_id' => $this->productA->id,
            'discount_percentage' => 20.00,
        ]);

        $promo = Promotion::where('code', 'PANJABI20')->first();

        // Edit promotion
        $editResponse = $this->actingAs($this->superAdmin)->put(route('promotions.update', $promo), [
            'name' => 'Panjabi Mega Discount',
            'code' => 'PANJABI25',
            'discount_percentage' => 25.00,
            'product_id' => $this->productA->id,
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'is_active' => true,
        ]);

        $editResponse->assertRedirect(route('promotions.index'));
        $this->assertDatabaseHas('promotions', [
            'id' => $promo->id,
            'name' => 'Panjabi Mega Discount',
            'code' => 'PANJABI25',
            'discount_percentage' => 25.00,
        ]);
    }

    public function test_product_specific_discount_only_applies_to_its_assigned_product_during_checkout(): void
    {
        // Create 20% discount on Panjabi (Product A)
        $promo = Promotion::create([
            'name' => 'Panjabi 20% OFF',
            'code' => 'PANJABI20',
            'discount_percentage' => 20.00,
            'product_id' => $this->productA->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $checkoutService = app(PosCheckoutService::class);

        // Cart with 1 Panjabi (2000 BDT) and 1 Jeans (1500 BDT)
        // Subtotal = 3500.00
        // Expected discount: ONLY 20% on Panjabi (2000 * 0.20 = 400.00)
        // Jeans (1500) has NO discount!
        // Total discount = 400.00
        $items = [
            ['product_variant_id' => $this->variantA->id, 'quantity' => 1],
            ['product_variant_id' => $this->variantB->id, 'quantity' => 1],
        ];

        $payments = [
            [
                'payment_method' => 'cash',
                'amount' => 3255.00,
                'tendered_amount' => 3255.00,
            ]
        ];

        $sale = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            $items,
            $payments,
            null,
            'PANJABI20'
        );

        // Verify discount was exactly 400 BDT (20% of 2000 BDT for Panjabi)
        $this->assertEquals(400.00, (float) $sale->discount_amount);
        $this->assertEquals($promo->id, $sale->promotion_id);

        // Verify line totals in sale items
        $panjabiItem = $sale->items->where('product_variant_id', $this->variantA->id)->first();
        $jeansItem = $sale->items->where('product_variant_id', $this->variantB->id)->first();

        $this->assertEquals(400.00, (float) $panjabiItem->discount_amount);
        $this->assertEquals(0.00, (float) $jeansItem->discount_amount);
    }

    public function test_product_specific_discount_does_not_apply_when_only_other_products_are_purchased(): void
    {
        // Create 20% discount on Panjabi (Product A)
        Promotion::create([
            'name' => 'Panjabi 20% OFF',
            'code' => 'PANJABI20',
            'discount_percentage' => 20.00,
            'product_id' => $this->productA->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $checkoutService = app(PosCheckoutService::class);

        // Cart with ONLY Jeans (Product B)
        $items = [
            ['product_variant_id' => $this->variantB->id, 'quantity' => 1],
        ];

        $payments = [
            [
                'payment_method' => 'cash',
                'amount' => 1575.00,
                'tendered_amount' => 1575.00,
            ]
        ];

        $sale = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            $items,
            $payments,
            null,
            'PANJABI20'
        );

        // Panjabi was not in cart, discount should be 0 and promo unlinked
        $this->assertEquals(0.00, (float) $sale->discount_amount);
        $this->assertNull($sale->promotion_id);
    }
}
