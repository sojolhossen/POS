<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSalePayment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RolePermission;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionAndSalesHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $superAdmin;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Main Outlet',
            'code' => 'MO-01',
            'address' => 'Dhaka',
            'phone' => '01700000000',
            'is_active' => true,
            'is_warehouse' => false,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Administrator (Owner)',
            'email' => 'owner@fashionpos.test',
            'password' => bcrypt('password'),
            'role' => UserRole::SUPER_ADMIN,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->cashier = User::create([
            'name' => 'Cashier Staff',
            'email' => 'cashier@fashionpos.test',
            'password' => bcrypt('password'),
            'role' => UserRole::CASHIER,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);
    }

    public function test_sales_history_page_renders_with_sales_records_and_stats(): void
    {
        $sale = PosSale::create([
            'invoice_number' => 'POS-20261017-0001',
            'store_id' => $this->store->id,
            'cashier_id' => $this->cashier->id,
            'customer_name' => 'John Doe',
            'customer_phone' => '01711223344',
            'subtotal_amount' => 1000.00,
            'discount_amount' => 50.00,
            'tax_rate' => 5.00,
            'tax_amount' => 47.50,
            'rounding_adjustment' => 0.50,
            'total_payable' => 998.00,
            'total_paid' => 1000.00,
            'change_returned' => 2.00,
            'status' => 'completed',
        ]);

        PosSalePayment::create([
            'pos_sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 998.00,
            'tendered_amount' => 1000.00,
            'change_amount' => 2.00,
            'status' => 'completed',
            'created_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('pos.sales.history'));

        $response->assertStatus(200);
        $response->assertSee('POS-20261017-0001');
        $response->assertSee('John Doe');
        $response->assertSee('01711223344');
        $response->assertSee('998.00');
    }

    public function test_super_admin_can_view_and_update_role_permission_matrix(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('roles.permissions.index'));
        $response->assertStatus(200);
        $response->assertSee('Role-Based Permission Matrix');
        $response->assertSee('Permanent Owner Bypass');

        // Revoke cashier's access to 'returns' and 'sales_history'
        $updateResponse = $this->actingAs($this->superAdmin)->post(route('roles.permissions.update'), [
            'permissions' => [
                'cashier' => [
                    'pos' => '1',
                    'sales_history' => '0',
                    'returns' => '0',
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('roles.permissions.index'));
        $updateResponse->assertSessionHas('success');

        // Verify in database
        $this->assertFalse(RolePermission::isAllowed('cashier', 'returns'));
        $this->assertFalse(RolePermission::isAllowed('cashier', 'sales_history'));
        $this->assertTrue(RolePermission::isAllowed('cashier', 'pos'));

        // Super Admin (shop owner) is always allowed regardless of database rows
        $this->assertTrue(RolePermission::isAllowed('super_admin', 'returns'));
        $this->assertTrue($this->superAdmin->canAccessModule('returns'));
    }

    public function test_locked_module_blocks_direct_access_via_middleware(): void
    {
        // Explicitly lock sales_history for cashier
        RolePermission::updateOrCreate(
            ['role' => 'cashier', 'module' => 'sales_history'],
            ['is_allowed' => false]
        );

        $this->assertFalse($this->cashier->canAccessModule('sales_history'));

        // Cashier attempts to visit locked sales history
        $response = $this->actingAs($this->cashier)->get(route('pos.sales.history'));

        // Redirected to dashboard with lock message
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_non_super_admin_cannot_access_roles_permissions_panel(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('roles.permissions.index'));
        $response->assertStatus(403);
    }
}
