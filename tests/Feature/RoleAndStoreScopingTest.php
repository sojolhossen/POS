<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndStoreScopingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Store Manager cannot access another store's restricted records.
     */
    public function test_store_manager_cannot_access_another_stores_data(): void
    {
        $storeA = Store::create([
            'name' => 'Store A',
            'code' => 'STR-001',
            'is_active' => true,
        ]);

        $storeB = Store::create([
            'name' => 'Store B',
            'code' => 'STR-002',
            'is_active' => true,
        ]);

        $managerA = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $storeA->id,
            'is_active' => true,
        ]);

        // Manager A accesses their own store -> OK
        $responseA = $this->actingAs($managerA)->get(route('stores.show', $storeA));
        $responseA->assertStatus(200);

        // Manager A tries accessing Store B via URL / ID manipulation -> 403 Forbidden!
        $responseB = $this->actingAs($managerA)->get(route('stores.show', $storeB));
        $responseB->assertStatus(403);
    }

    /**
     * Test Store Manager assigned to multiple stores can view all assigned stores.
     */
    public function test_store_manager_can_access_all_assigned_managed_stores(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'is_active' => true,
        ]);

        $store1 = Store::create([
            'name' => 'Flagship Store',
            'code' => 'STR-101',
            'manager_id' => $manager->id,
            'is_active' => true,
        ]);

        $store2 = Store::create([
            'name' => 'Outlet Store',
            'code' => 'STR-102',
            'manager_id' => $manager->id,
            'is_active' => true,
        ]);

        $store3 = Store::create([
            'name' => 'Other Branch',
            'code' => 'STR-103',
            'manager_id' => null,
            'is_active' => true,
        ]);

        $manager->update(['store_id' => $store1->id]);

        // Manager can view the stores directory and sees both managed stores
        $response = $this->actingAs($manager)->get(route('stores.index'));
        $response->assertStatus(200);
        $response->assertSee('Flagship Store');
        $response->assertSee('Outlet Store');
        $response->assertDontSee('Other Branch');

        // Manager can view details of both managed stores
        $this->actingAs($manager)->get(route('stores.show', $store1))->assertStatus(200);
        $this->actingAs($manager)->get(route('stores.show', $store2))->assertStatus(200);

        // Manager cannot view store 3
        $this->actingAs($manager)->get(route('stores.show', $store3))->assertStatus(403);
    }

    /**
     * Test Cashier cannot access administrative routes.
     */
    public function test_cashier_cannot_access_admin_store_or_staff_routes(): void
    {
        $cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'is_active' => true,
        ]);

        // Attempt to view staff management
        $responseStaff = $this->actingAs($cashier)->get('/staff');
        $responseStaff->assertStatus(403);

        // Attempt to create new store
        $responseStore = $this->actingAs($cashier)->get('/admin/stores/create');
        $responseStore->assertStatus(403);

        // Attempt to view audit logs
        $responseAudit = $this->actingAs($cashier)->get('/audit-logs');
        $responseAudit->assertStatus(403);
    }

    /**
     * Test Super Admin can access all stores and administrative features.
     */
    public function test_super_admin_can_access_all_stores_and_admin_modules(): void
    {
        $store = Store::create([
            'name' => 'Any Store',
            'code' => 'STR-099',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('stores.show', $store))->assertStatus(200);
        $this->actingAs($admin)->get('/staff')->assertStatus(200);
        $this->actingAs($admin)->get('/audit-logs')->assertStatus(200);
    }
}
