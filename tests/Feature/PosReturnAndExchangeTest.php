<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DailyCashRegister;
use App\Models\PosReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\PosCheckoutService;
use App\Services\PosReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReturnAndExchangeTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected ProductVariant $variant;
    protected User $cashier;
    protected User $storeManager;
    protected Customer $customer;
    protected DailyCashRegister $register;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Uttara Branch',
            'code' => 'UTR-01',
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'WOM',
            'name' => 'Womens Wear',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Embroidered Kurti',
            'sku' => 'KURTI-001',
            'category_id' => $category->id,
            'base_price' => 1500.00,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Crimson Red',
            'size' => 'M',
            'sku' => 'KURTI-001-RED-M',
            'barcode' => '894440010001',
            'purchase_price' => 600.00,
            'weighted_average_cost' => 600.00,
            'selling_price' => 1500.00,
            'is_active' => true,
        ]);

        StockBalance::create([
            'store_id' => $this->store->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand' => 20,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        $this->customer = Customer::create([
            'name' => 'Fatima Begum',
            'phone' => '01812345678',
            'total_spend' => 0,
            'loyalty_points' => 0,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->storeManager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $cashService = app(CashRegisterService::class);
        $this->register = $cashService->openRegister($this->store, $this->cashier, 5000.00);
    }

    /**
     * Test return within 7 days restores sellable stock, reverses loyalty points, and settles cash refund.
     */
    public function test_return_within_7_days_restores_sellable_stock_and_reverses_loyalty_points(): void
    {
        $checkoutService = app(PosCheckoutService::class);
        $returnService = app(PosReturnService::class);

        // 1. Initial sale of 2 Kurtis to customer
        // 2 * 1500 = 3000 + 5% VAT (150) = 3150.00
        $sale = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            [['product_variant_id' => $this->variant->id, 'quantity' => 2]],
            [['payment_method' => 'cash', 'amount' => 3150.00]],
            $this->customer
        );

        // Check stock reduced to 18
        $balance = StockBalance::where('store_id', $this->store->id)->where('product_variant_id', $this->variant->id)->first();
        $this->assertEquals(18, $balance->quantity_on_hand);

        // Check customer earned loyalty points (3150 / 100 = 31 points)
        $this->customer->refresh();
        $this->assertGreaterThan(0, $this->customer->loyalty_points);
        $initialEarnedPoints = $this->customer->loyalty_points;

        // 2. Return 1 Kurti in "sellable" condition 2 days later
        $saleItem = $sale->items->first();
        $returnItems = [
            [
                'pos_sale_item_id' => $saleItem->id,
                'quantity' => 1,
                'condition' => 'sellable',
                'reason' => 'Customer preferred different color'
            ]
        ];

        $posReturn = $returnService->processReturn(
            $sale,
            $this->store,
            $this->cashier,
            $returnItems,
            'refund',
            'cash',
            false // has receipt
        );

        $this->assertNotNull($posReturn->id);
        $this->assertEquals(1500.00, (float) $posReturn->total_refund_amount);

        // Sellable stock must increase back from 18 to 19
        $balance->refresh();
        $this->assertEquals(19, $balance->quantity_on_hand);
        $this->assertEquals(0, $balance->damaged_quantity);

        // Loyalty points should be reversed proportionally
        $this->customer->refresh();
        $this->assertLessThan($initialEarnedPoints, $this->customer->loyalty_points);

        // Cash register should record cash refund
        $this->register->refresh();
        $this->assertEquals(1500.00, (float) $this->register->cash_refunds);
    }

    /**
     * Test damaged return does NOT increase sellable stock, but increments damaged stock.
     */
    public function test_damaged_return_segregated_outside_sellable_inventory(): void
    {
        $checkoutService = app(PosCheckoutService::class);
        $returnService = app(PosReturnService::class);

        $sale = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            [['product_variant_id' => $this->variant->id, 'quantity' => 1]],
            [['payment_method' => 'cash', 'amount' => 1575.00]]
        );

        $saleItem = $sale->items->first();
        $returnItems = [
            [
                'pos_sale_item_id' => $saleItem->id,
                'quantity' => 1,
                'condition' => 'damaged',
                'reason' => 'Torn sleeve detected'
            ]
        ];

        $posReturn = $returnService->processReturn(
            $sale,
            $this->store,
            $this->cashier,
            $returnItems,
            'refund',
            'cash'
        );

        $balance = StockBalance::where('store_id', $this->store->id)->where('product_variant_id', $this->variant->id)->first();
        // Sellable stock should remain 19 (did NOT go back to sellable shelf)
        $this->assertEquals(19, $balance->quantity_on_hand);
        // Damaged quantity should be 1
        $this->assertEquals(1, $balance->damaged_quantity);
    }

    /**
     * Test return beyond 7 days without manager approval is rejected.
     */
    public function test_expired_return_without_manager_approval_is_rejected(): void
    {
        $checkoutService = app(PosCheckoutService::class);
        $returnService = app(PosReturnService::class);

        $sale = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            [['product_variant_id' => $this->variant->id, 'quantity' => 1]],
            [['payment_method' => 'cash', 'amount' => 1575.00]]
        );

        // Manually age the sale beyond 7 days
        $sale->created_at = now()->subDays(10);
        $sale->save();

        $saleItem = $sale->items->first();
        $returnItems = [
            ['pos_sale_item_id' => $saleItem->id, 'quantity' => 1, 'condition' => 'sellable']
        ];

        // Attempt return without manager approval
        $this->expectException(\Exception::class);
        $returnService->processReturn(
            $sale,
            $this->store,
            $this->cashier,
            $returnItems,
            'refund',
            'cash',
            false,
            null // NO manager approval
        );
    }

    /**
     * Test returning an item sold with a discount refunds the net amount paid, NOT regular catalog price.
     * Prevents register discrepancy and hidden loss of money.
     */
    public function test_discounted_sale_return_refunds_net_paid_price_and_protects_cash_balance(): void
    {
        \App\Models\AppSetting::set('vat_enabled', '0');
        \App\Models\AppSetting::set('vat_rate', '0.00');

        $checkoutService = app(PosCheckoutService::class);
        $returnService = app(PosReturnService::class);

        // Product regular price is 220.00
        $product = Product::create([
            'name' => 'MD SAJOL HOSSEN SHIRT',
            'sku' => 'SAJOL-001',
            'category_id' => $this->variant->product->category_id,
            'base_price' => 220.00,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Navy Blue',
            'size' => 'L',
            'sku' => 'SAJOL-001-BLU-L',
            'barcode' => '880329627699',
            'purchase_price' => 100.00,
            'weighted_average_cost' => 100.00,
            'selling_price' => 220.00,
            'is_active' => true,
        ]);

        StockBalance::create([
            'store_id' => $this->store->id,
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        // Create 10% promotion
        $promotion = \App\Models\Promotion::create([
            'name' => '10 Percent Discount',
            'code' => 'DISC10',
            'discount_percentage' => 10.00,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
            'created_by' => $this->storeManager->id,
        ]);

        $initialCashInHand = (float) $this->register->cash_in_hand_expected;

        // Sale: 220.00 with 10% discount (22.00) = 198.00 payable
        $sale = $checkoutService->checkout(
            $this->store,
            $this->cashier,
            [['product_variant_id' => $variant->id, 'quantity' => 1]],
            [['payment_method' => 'cash', 'amount' => 198.00, 'tendered_amount' => 198.00]],
            null,
            'DISC10'
        );

        $this->assertEquals(198.00, (float) $sale->total_payable);
        $this->assertEquals(22.00, (float) $sale->discount_amount);

        // Verify register received 198.00 cash
        $this->register->refresh();
        $this->assertEquals($initialCashInHand + 198.00, (float) $this->register->cash_in_hand_expected);

        // Now process customer return & refund
        $saleItem = $sale->items->first();
        $this->assertEquals(220.00, (float) $saleItem->unit_price);
        $this->assertEquals(198.00, (float) $saleItem->line_total);

        $posReturn = $returnService->processReturn(
            $sale,
            $this->store,
            $this->cashier,
            [
                [
                    'pos_sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                    'condition' => 'sellable',
                    'reason' => 'Customer return request'
                ]
            ],
            'refund',
            'cash',
            false
        );

        // 1. Returned value must be EXACTLY 198.00 (NOT 220.00!)
        $this->assertEquals(198.00, (float) $posReturn->total_returned_value);
        $this->assertEquals(-198.00, (float) $posReturn->net_settlement_amount);

        // 2. Return line item unit price and line total must be 198.00
        $returnItem = $posReturn->items->first();
        $this->assertEquals(198.00, (float) $returnItem->unit_price);
        $this->assertEquals(198.00, (float) $returnItem->line_total);
        $this->assertEquals(198.00, (float) $returnItem->refund_amount);

        // 3. Cash register must decrement by EXACTLY 198.00 (back to initial cash in hand)
        $this->register->refresh();
        $this->assertEquals($initialCashInHand, (float) $this->register->cash_in_hand_expected);
        $this->assertEquals(198.00, (float) $this->register->cash_refunds_amount);
    }
}

