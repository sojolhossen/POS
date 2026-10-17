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
use App\Services\CashRegisterService;
use App\Services\PosCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected ProductVariant $variantA;
    protected ProductVariant $variantB;
    protected User $cashier;
    protected User $superAdmin;
    protected DailyCashRegister $register;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Gulshan Flagship Store',
            'code' => 'GLS-01',
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'GAR',
            'name' => 'Garments',
            'is_active' => true,
        ]);

        $productA = Product::create([
            'name' => 'Cotton Polo Shirt',
            'sku' => 'POLO-001',
            'category_id' => $category->id,
            'base_price' => 850.00,
            'is_active' => true,
        ]);

        $this->variantA = ProductVariant::create([
            'product_id' => $productA->id,
            'color' => 'Black',
            'size' => 'M',
            'sku' => 'POLO-001-BLK-M',
            'barcode' => '894330010001',
            'purchase_price' => 350.00,
            'weighted_average_cost' => 350.00,
            'selling_price' => 850.00,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'name' => 'Slim Fit Denim Jeans',
            'sku' => 'JEANS-001',
            'category_id' => $category->id,
            'base_price' => 1800.00,
            'is_active' => true,
        ]);

        $this->variantB = ProductVariant::create([
            'product_id' => $productB->id,
            'color' => 'Deep Indigo',
            'size' => '32',
            'sku' => 'JEANS-001-IND-32',
            'barcode' => '894330020032',
            'purchase_price' => 700.00,
            'weighted_average_cost' => 700.00,
            'selling_price' => 1800.00,
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
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // Open daily register for today
        $cashService = app(CashRegisterService::class);
        $this->register = $cashService->openRegister($this->store, $this->cashier, 2500.00);
    }

    /**
     * Test barcode scan lookup endpoint.
     */
    public function test_barcode_scan_lookup_returns_variant_and_available_stock(): void
    {
        $response = $this->actingAs($this->cashier)
            ->getJson(route('pos.search', ['q' => '894330010001']));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('894330010001', $data[0]['barcode']);
        $this->assertEquals(10, $data[0]['available_stock']);
        $this->assertEquals(850.00, $data[0]['price']);
    }

    /**
     * Test checkout with promotion, tax, BDT rounding, split payment and daily cash update.
     */
    public function test_pos_checkout_atomic_split_payment_and_rounding(): void
    {
        // 1. Create a 10% promotion
        $promotion = Promotion::create([
            'name' => 'Weekend Super Sale',
            'code' => 'WEEKEND10',
            'discount_percentage' => 10.00,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(2),
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $checkoutService = app(PosCheckoutService::class);

        // Buy 1 Polo (850) and 1 Jeans (1800) -> Subtotal = 2650.00
        // Promo 10% = -265.00 -> Net = 2385.00
        // VAT 5% = +119.25 -> Raw Total = 2504.25
        // BDT Rounding = 2504.00 (Rounding diff = -0.25)
        $items = [
            ['product_variant_id' => $this->variantA->id, 'quantity' => 1],
            ['product_variant_id' => $this->variantB->id, 'quantity' => 1],
        ];

        // Split payments: Cash ৳1504, bKash ৳1000 -> Total ৳2504
        $payments = [
            [
                'payment_method' => 'cash',
                'amount' => 1504.00,
                'tendered_amount' => 2000.00, // Gave 2000 cash, change = 496.00
            ],
            [
                'payment_method' => 'bkash',
                'amount' => 1000.00,
                'reference' => 'BK-99120',
            ]
        ];

        $sale = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            $items,
            $payments,
            null,
            'WEEKEND10',
            0,
            'IDEM-UNIQUE-KEY-001'
        );

        $this->assertNotNull($sale->id);
        $this->assertNotNull($sale->invoice_number);
        $this->assertEquals(2650.00, (float) $sale->subtotal);
        $this->assertEquals(265.00, (float) $sale->discount_amount);
        $this->assertEquals(119.25, (float) $sale->tax_amount);
        $this->assertEquals(2504.00, (float) $sale->total_amount);

        // Verify stock deducted correctly
        $balA = StockBalance::where('store_id', $this->store->id)->where('product_variant_id', $this->variantA->id)->first();
        $balB = StockBalance::where('store_id', $this->store->id)->where('product_variant_id', $this->variantB->id)->first();
        $this->assertEquals(9, $balA->quantity_on_hand);
        $this->assertEquals(4, $balB->quantity_on_hand);

        // Verify daily cash register incremented by cash portion (1504.00)
        $this->register->refresh();
        $this->assertEquals(1504.00, (float) $this->register->cash_sales);
        $this->assertEquals(4004.00, (float) $this->register->expected_closing_cash); // 2500 opening + 1504 cash sales

        // Verify cash change calculated
        $cashPayment = $sale->payments()->where('payment_method', 'cash')->first();
        $this->assertEquals(496.00, (float) $cashPayment->change_amount);
    }

    /**
     * Test idempotency key prevents duplicate submission and overselling.
     */
    public function test_idempotent_checkout_replay_protection(): void
    {
        $checkoutService = app(PosCheckoutService::class);

        $items = [['product_variant_id' => $this->variantA->id, 'quantity' => 2]];
        // 2 * 850 = 1700 + 5% VAT (85) = 1785.00
        $payments = [['payment_method' => 'cash', 'amount' => 1785.00, 'tendered_amount' => 1785.00]];

        // First checkout call
        $sale1 = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            $items,
            $payments,
            null,
            null,
            0,
            'IDEM-REPLAY-KEY-777'
        );

        $balAfterSale1 = StockBalance::where('store_id', $this->store->id)->where('product_variant_id', $this->variantA->id)->first()->quantity_on_hand;
        $this->assertEquals(8, $balAfterSale1); // 10 - 2 = 8

        // Second checkout call with EXACT SAME idempotency key (simulating duplicate browser click or retry)
        $sale2 = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            $items,
            $payments,
            null,
            null,
            0,
            'IDEM-REPLAY-KEY-777'
        );

        // Must return the SAME sale record without deducting stock again
        $this->assertEquals($sale1->id, $sale2->id);
        $this->assertEquals($sale1->invoice_number, $sale2->invoice_number);

        $balAfterSale2 = StockBalance::where('store_id', $this->store->id)->where('product_variant_id', $this->variantA->id)->first()->quantity_on_hand;
        $this->assertEquals(8, $balAfterSale2); // Stock must remain 8!
    }

    /**
     * Test payment total mismatch is rejected by server validation.
     */
    public function test_payment_total_mismatch_is_rejected(): void
    {
        $checkoutService = app(PosCheckoutService::class);

        $items = [['product_variant_id' => $this->variantA->id, 'quantity' => 1]];
        // Net payable is 850 + 5% = 892.50 -> 893.00
        // Provided payment only 500.00
        $payments = [['payment_method' => 'cash', 'amount' => 500.00]];

        $this->expectException(\Exception::class);
        $checkoutService->checkout(
            $this->store,
            $this->cashier,
            $items,
            $payments
        );
    }

    /**
     * Test checkout rejects when customer tendered cash is less than payment amount (shortage / due).
     */
    public function test_cash_tendered_less_than_amount_is_rejected(): void
    {
        $checkoutService = app(PosCheckoutService::class);

        $items = [['product_variant_id' => $this->variantA->id, 'quantity' => 1]];
        // Net payable is 893.00, customer gave only 800.00 cash
        $payments = [
            ['payment_method' => 'cash', 'amount' => 893.00, 'tendered_amount' => 800.00]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tendered cash (৳800) is less than allocated cash payment (৳893)');
        $checkoutService->checkout(
            $this->store,
            $this->cashier,
            $items,
            $payments
        );
    }

    /**
     * Test reprinting receipt retains original invoice number and does not create new sale.
     */
    public function test_reprint_retains_original_invoice_number(): void
    {
        $checkoutService = app(PosCheckoutService::class);
        $items = [['product_variant_id' => $this->variantA->id, 'quantity' => 1]];
        $payments = [['payment_method' => 'cash', 'amount' => 893.00]];

        $sale = $checkoutService->checkout($this->store, $this->cashier, $items, $payments);

        $initialSalesCount = \App\Models\PosSale::count();

        $response = $this->actingAs($this->cashier)->get(route('pos.reprint', $sale));
        $response->assertStatus(200);
        $response->assertSee($sale->invoice_number);
        $response->assertSee('DUPLICATE REPRINT');

        // Sales count must NOT increase
        $this->assertEquals($initialSalesCount, \App\Models\PosSale::count());
    }
}
