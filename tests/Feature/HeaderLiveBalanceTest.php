<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DailyCashRegister;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderLiveBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_total_live_balance_of_all_stores_today(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $store1 = Store::create(['name' => 'Store 1', 'code' => 'S1', 'is_active' => true]);
        $store2 = Store::create(['name' => 'Store 2', 'code' => 'S2', 'is_active' => true]);

        DailyCashRegister::create([
            'store_id' => $store1->id,
            'business_date' => now()->toDateString(),
            'opening_cash' => 2000.00,
            'cash_sales_amount' => 500.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 2500.00,
            'status' => 'open',
            'opened_by' => $superAdmin->id,
        ]);

        DailyCashRegister::create([
            'store_id' => $store2->id,
            'business_date' => now()->toDateString(),
            'opening_cash' => 1000.00,
            'cash_sales_amount' => 300.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 1300.00,
            'status' => 'open',
            'opened_by' => $superAdmin->id,
        ]);

        /** @var StoreBalanceService $service */
        $service = app(StoreBalanceService::class);
        $data = $service->getHeaderBalanceData($superAdmin);

        // Super Admin gets total of both stores (2500 + 1300 = 3800)
        $this->assertEquals(3800.00, $data['liveBalance']);
        $this->assertEquals('All Stores Today', $data['scopeName']);
        $this->assertCount(2, $data['storeBreakdown']);

        // Check header rendering in response
        $response = $this->actingAs($superAdmin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('৳3,800.00');
        $response->assertSee('All Stores Today');
    }

    public function test_store_manager_only_sees_live_balance_of_assigned_stores(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'is_active' => true,
        ]);

        $managerStore1 = Store::create(['name' => 'Manager Store 1', 'code' => 'MS1', 'is_active' => true, 'manager_id' => $manager->id]);
        $otherStore = Store::create(['name' => 'Other Store', 'code' => 'OS1', 'is_active' => true]);

        $manager->update(['store_id' => $managerStore1->id]);

        DailyCashRegister::create([
            'store_id' => $managerStore1->id,
            'business_date' => now()->toDateString(),
            'opening_cash' => 1500.00,
            'cash_in_hand_expected' => 1500.00,
            'status' => 'open',
            'opened_by' => $manager->id,
        ]);

        DailyCashRegister::create([
            'store_id' => $otherStore->id,
            'business_date' => now()->toDateString(),
            'opening_cash' => 5000.00,
            'cash_in_hand_expected' => 5000.00,
            'status' => 'open',
            'opened_by' => $manager->id,
        ]);

        /** @var StoreBalanceService $service */
        $service = app(StoreBalanceService::class);
        $data = $service->getHeaderBalanceData($manager);

        // Manager only gets their assigned store (1500.00), NOT other store (5000.00)
        $this->assertEquals(1500.00, $data['liveBalance']);
        $this->assertEquals('Manager Store 1', $data['scopeName']);
        $this->assertCount(1, $data['storeBreakdown']);

        // Check header rendering
        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('৳1,500.00');
        $response->assertDontSee('৳6,500.00');
    }

    public function test_cashier_only_sees_assigned_store_live_balance(): void
    {
        $cashierStore = Store::create(['name' => 'Cashier Branch', 'code' => 'CB1', 'is_active' => true]);
        $secretStore = Store::create(['name' => 'Secret Store', 'code' => 'SS1', 'is_active' => true]);

        $cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'store_id' => $cashierStore->id,
            'is_active' => true,
        ]);

        DailyCashRegister::create([
            'store_id' => $cashierStore->id,
            'business_date' => now()->toDateString(),
            'opening_cash' => 800.00,
            'cash_in_hand_expected' => 800.00,
            'status' => 'open',
            'opened_by' => $cashier->id,
        ]);

        DailyCashRegister::create([
            'store_id' => $secretStore->id,
            'business_date' => now()->toDateString(),
            'opening_cash' => 9999.00,
            'cash_in_hand_expected' => 9999.00,
            'status' => 'open',
            'opened_by' => $cashier->id,
        ]);

        /** @var StoreBalanceService $service */
        $service = app(StoreBalanceService::class);
        $data = $service->getHeaderBalanceData($cashier);

        $this->assertEquals(800.00, $data['liveBalance']);
        $this->assertEquals('Cashier Branch', $data['scopeName']);
    }
}
