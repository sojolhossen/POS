<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DailyCashRegister;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScheduledTasksAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Store $warehouse;
    protected User $superAdmin;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Store::create(['name' => 'Central Depot', 'code' => 'WH-01', 'is_warehouse' => true, 'is_active' => true]);
        $this->store = Store::create(['name' => 'Gulshan Store', 'code' => 'ST-GLS', 'is_active' => true]);

        $this->superAdmin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pos.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Store Manager',
            'email' => 'manager@pos.test',
            'password' => bcrypt('password'),
            'role' => 'store_manager',
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);
    }

    public function test_scheduled_task_detects_low_stock_and_dispatches_notifications(): void
    {
        $cat = Category::create(['name' => 'Pants', 'code' => 'PNT', 'is_active' => true]);
        $prod = Product::create(['category_id' => $cat->id, 'name' => 'Chino Trouser', 'sku' => 'CHINO-01', 'base_price' => 2200.00, 'is_active' => true]);
        $var = ProductVariant::create([
            'product_id' => $prod->id,
            'size' => '32',
            'color' => 'Navy',
            'barcode' => '894000332211',
            'selling_price' => 2200.00,
            'purchase_price' => 1100.00,
            'weighted_average_cost' => 1100.00,
            'is_active' => true,
        ]);

        // Stock quantity 2 is below default alert threshold of 5
        StockBalance::create([
            'store_id' => $this->store->id,
            'product_variant_id' => $var->id,
            'quantity_on_hand' => 2,
            'quantity_reserved' => 0,
            'damaged_quantity' => 0,
        ]);

        $exitCode = Artisan::call('pos:run-scheduled-tasks');
        $this->assertEquals(0, $exitCode);

        // Notification persisted in notifications table
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->superAdmin->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_scheduled_task_detects_delayed_transfers(): void
    {
        // Transfer dispatched 3 days ago, still in_transit
        StockTransfer::create([
            'source_store_id' => $this->warehouse->id,
            'destination_store_id' => $this->store->id,
            'reference_number' => 'TR-DELAYED-01',
            'status' => 'in_transit',
            'created_by' => $this->superAdmin->id,
            'dispatched_by' => $this->superAdmin->id,
            'dispatched_at' => now()->subDays(3),
            'created_at' => now()->subDays(3),
        ]);

        $exitCode = Artisan::call('pos:run-scheduled-tasks');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->superAdmin->id,
        ]);
    }

    public function test_scheduled_task_detects_unclosed_cash_drawers(): void
    {
        DailyCashRegister::create([
            'store_id' => $this->store->id,
            'business_date' => now()->format('Y-m-d'),
            'opened_by' => $this->manager->id,
            'opened_at' => now()->subHours(10),
            'opening_cash' => 5000.00,
            'cash_sales_amount' => 15000.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 20000.00,
            'status' => 'open',
        ]);

        $exitCode = Artisan::call('pos:run-scheduled-tasks');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->manager->id,
        ]);
    }
}
