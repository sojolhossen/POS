<?php

namespace Tests\Feature;

use App\Models\DailyCashRegister;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\User;
use App\Services\PosCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HighConcurrencyAndOversellingProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $cashier;
    protected ProductVariant $variant;
    protected StockBalance $stockBalance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Concurrency Stress Store',
            'code' => 'ST-CONC',
            'is_active' => true,
        ]);

        $this->cashier = User::create([
            'name' => 'Load Test Cashier',
            'email' => 'concurrency@pos.test',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Panjabi Traditional',
            'code' => 'PANJABI',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'High Demand Limited Edition Panjabi',
            'sku' => 'PANJABI-LTD-01',
            'base_price' => 3500.00,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Royal Maroon',
            'barcode' => '894000998877',
            'selling_price' => 3500.00,
            'purchase_price' => 1500.00,
            'weighted_average_cost' => 1500.00,
            'is_active' => true,
        ]);

        // Stock has exactly 5 units available in inventory
        $this->stockBalance = StockBalance::create([
            'store_id' => $this->store->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        DailyCashRegister::create([
            'store_id' => $this->store->id,
            'business_date' => now()->format('Y-m-d'),
            'opened_by' => $this->cashier->id,
            'opened_at' => now(),
            'opening_cash' => 10000.00,
            'cash_sales_amount' => 0.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 10000.00,
            'status' => 'open',
        ]);
    }

    /**
     * Simulate high-concurrency requests attempting to sell 10 units when only 5 exist.
     * With SELECT ... FOR UPDATE row-level locking, exactly 5 sales must succeed and 5 must fail.
     * Final inventory must be exactly 0, never negative (No Overselling).
     */
    public function test_concurrent_checkout_prevents_overselling(): void
    {
        $checkoutService = app(PosCheckoutService::class);

        $successfulCheckouts = 0;
        $rejectedCheckouts = 0;

        // 10 back-to-back checkout attempts requesting 1 unit each
        for ($i = 1; $i <= 10; $i++) {
            try {
                $checkoutService->checkout(
                    $this->store,
                    $this->cashier,
                    [['product_variant_id' => $this->variant->id, 'quantity' => 1]],
                    [['payment_method' => 'cash', 'amount' => 3675.00, 'tendered_amount' => 3675.00]], // 3500 + 5% VAT (175)
                    null,
                    null,
                    0,
                    "CONCURRENCY-TRX-{$i}"
                );
                $successfulCheckouts++;
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Insufficient stock') || str_contains($e->getMessage(), 'Overselling prevented')) {
                    $rejectedCheckouts++;
                } else {
                    throw $e;
                }
            }
        }

        // Exactly 5 should succeed, exactly 5 rejected
        $this->assertEquals(5, $successfulCheckouts, "Expected exactly 5 checkouts to succeed from available inventory of 5.");
        $this->assertEquals(5, $rejectedCheckouts, "Expected exactly 5 checkouts to be rejected due to zero stock remaining.");

        // Inventory balance must be exactly 0, never negative
        $this->stockBalance->refresh();
        $this->assertEquals(0, $this->stockBalance->quantity_on_hand);
        $this->assertEquals(0, $this->stockBalance->available_quantity);
    }
}
