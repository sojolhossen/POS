<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\PosCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashier;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Flagship Store',
            'code' => 'FS-01',
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
    }

    public function test_super_admin_can_view_tax_settings_page()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.tax.index'));
        $response->assertStatus(200);
        $response->assertSee('Tax & VAT System Configuration', false);
        $response->assertSee('Master Tax / VAT Switch');
    }

    public function test_cashier_cannot_access_tax_settings_page()
    {
        $response = $this->actingAs($this->cashier)->get(route('admin.tax.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_turn_off_vat()
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.tax.update'), [
            'vat_enabled' => '0',
            'vat_rate' => '0.00',
            'vat_label' => 'Tax / VAT',
        ]);

        $response->assertRedirect();
        $this->assertFalse(AppSetting::isVatEnabled());
        $this->assertEquals(0.00, AppSetting::getVatRate());
    }

    public function test_super_admin_can_configure_custom_vat_rate()
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.tax.update'), [
            'vat_enabled' => '1',
            'vat_rate' => '7.50',
            'vat_label' => 'Retail VAT',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'vat_enabled' => true,
            'vat_rate' => 7.50,
            'vat_label' => 'Retail VAT',
        ]);

        $this->assertTrue(AppSetting::isVatEnabled());
        $this->assertEquals(7.50, AppSetting::getVatRate());
        $this->assertEquals('Retail VAT', AppSetting::getVatLabel());
    }

    public function test_checkout_applies_zero_tax_when_vat_is_disabled()
    {
        AppSetting::set('vat_enabled', '0', 'tax');
        AppSetting::set('vat_rate', '0.00', 'tax');

        \App\Models\DailyCashRegister::create([
            'store_id' => $this->store->id,
            'business_date' => now()->format('Y-m-d'),
            'opened_by' => $this->cashier->id,
            'opened_at' => now(),
            'opening_cash' => 5000.00,
            'cash_sales_amount' => 0.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 5000.00,
            'status' => 'open',
        ]);

        $cat = Category::create(['name' => 'Menswear', 'code' => 'MEN']);
        $prod = Product::create([
            'category_id' => $cat->id,
            'name' => 'Oxford Shirt',
            'sku' => 'PROD-OX-100',
            'article_code' => 'OX-100',
            'cost_price' => 500,
            'selling_price' => 1000,
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $prod->id,
            'sku' => 'OX-100-BLU-L',
            'barcode' => '8901234567890',
            'size' => 'L',
            'color' => 'Blue',
            'selling_price' => 1000,
        ]);

        \App\Models\StockBalance::create([
            'store_id' => $this->store->id,
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

        $service = app(PosCheckoutService::class);
        $sale = $service->checkout(
            $this->store,
            $this->cashier,
            [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
            [
                [
                    'payment_method' => 'cash',
                    'amount' => 1000.00,
                    'tendered_amount' => 1000.00,
                ],
            ],
            null,
            null,
            0,
            'TEST-TAX-OFF-' . uniqid()
        );

        $this->assertEquals(0.00, (float) $sale->tax_rate);
        $this->assertEquals(0.00, (float) $sale->tax_amount);
        $this->assertEquals(1000.00, (float) $sale->total_amount);
    }
}
