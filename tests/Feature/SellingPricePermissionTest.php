<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellingPricePermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test only Super Admin can change selling prices; Store Manager receives 403.
     */
    public function test_only_super_admin_can_change_selling_prices(): void
    {
        $category = Category::create(['name' => 'Shirts', 'code' => 'SH', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Oxford Shirt',
            'sku' => 'SH-000001',
            'category_id' => $category->id,
            'base_purchase_price' => 700.00,
            'base_selling_price' => 1500.00,
            'is_active' => true,
        ]);

        $storeManager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // Store Manager attempts to change price -> 403 Forbidden
        $responseManager = $this->actingAs($storeManager)->post(route('products.price.update', $product), [
            'new_price' => 1200.00,
            'reason' => 'Store manager attempted discount',
        ]);
        $responseManager->assertStatus(403);
        $this->assertEquals(1500.00, $product->fresh()->base_selling_price);

        // Super Admin changes price -> 302 Redirect with success
        $responseAdmin = $this->actingAs($superAdmin)->post(route('products.price.update', $product), [
            'new_price' => 1750.00,
            'reason' => 'Executive price increase for new collection',
        ]);
        $responseAdmin->assertRedirect(route('products.show', $product));

        // Verify updated price and recorded history
        $product->refresh();
        $this->assertEquals(1750.00, (float) $product->base_selling_price);

        $history = PriceHistory::where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertEquals(1500.00, (float) $history->old_price);
        $this->assertEquals(1750.00, (float) $history->new_price);
        $this->assertEquals('Executive price increase for new collection', $history->reason);
        $this->assertEquals($superAdmin->id, $history->user_id);
    }
}
