<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingTest extends TestCase
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

    public function test_super_admin_can_view_branding_settings_page()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.branding.index'));
        $response->assertStatus(200);
        $response->assertSee('Site Name & Logo Configuration', false);
        $response->assertSee('Brand / Site Logo', false);
    }

    public function test_non_super_admin_cannot_access_branding_settings_page()
    {
        $response = $this->actingAs($this->cashier)->get(route('admin.branding.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_update_site_name_and_details()
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.branding.update'), [
            'app_name' => 'Artisan Fashion BD',
            'app_tagline' => 'Luxury Clothing & Retail ERP',
            'company_address' => 'Gulshan-2, Dhaka',
            'company_phone' => '01999-888777',
            'company_email' => 'contact@artisanbd.com',
            'receipt_footer' => 'Visit www.artisanbd.com • All sales final',
        ]);

        $response->assertRedirect(route('admin.branding.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('Artisan Fashion BD', AppSetting::getAppName());
        $this->assertEquals('Luxury Clothing & Retail ERP', AppSetting::getAppTagline());
        $this->assertEquals('Gulshan-2, Dhaka', AppSetting::getCompanyAddress());
        $this->assertEquals('01999-888777', AppSetting::getCompanyPhone());
        $this->assertEquals('contact@artisanbd.com', AppSetting::getCompanyEmail());
        $this->assertEquals('Visit www.artisanbd.com • All sales final', AppSetting::getReceiptFooter());
    }

    public function test_super_admin_can_upload_and_remove_logo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('custom_logo.png', 300, 300);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.branding.update'), [
            'app_name' => 'Fashion Star',
            'logo' => $file,
        ]);

        $response->assertRedirect(route('admin.branding.index'));

        $logoPath = AppSetting::get('app_logo');
        $this->assertNotEmpty($logoPath);
        Storage::disk('public')->assertExists($logoPath);
        $this->assertStringContainsString($logoPath, AppSetting::getAppLogo());

        // Now remove the logo
        $response2 = $this->actingAs($this->superAdmin)->post(route('admin.branding.update'), [
            'app_name' => 'Fashion Star',
            'remove_logo' => '1',
        ]);

        $response2->assertRedirect(route('admin.branding.index'));
        $this->assertEmpty(AppSetting::get('app_logo'));
        $this->assertNull(AppSetting::getAppLogo());
    }

    public function test_barcode_label_prints_configured_site_name_at_brand_name_position()
    {
        AppSetting::set('app_name', 'Artisan Clothing BD', 'branding');

        $cat = \App\Models\Category::create(['name' => 'T-Shirt', 'code' => 'TSH', 'is_active' => true]);
        $prod = \App\Models\Product::create([
            'name' => 'Classic Tee',
            'sku' => 'TSH-001',
            'category_id' => $cat->id,
            'base_purchase_price' => 300,
            'base_selling_price' => 750,
            'is_active' => true,
        ]);

        $variant = \App\Models\ProductVariant::create([
            'product_id' => $prod->id,
            'size' => 'XL',
            'color' => 'Black',
            'barcode' => '880000009999',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('barcodes.print'), [
            'single_variant_id' => $variant->id,
            'single_quantity' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Artisan Clothing BD');
        $response->assertViewHas('labels', function ($labels) {
            return $labels[0]['brand_name'] === 'Artisan Clothing BD';
        });
    }
}

