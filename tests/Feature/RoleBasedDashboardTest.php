<?php

namespace Tests\Feature;

use App\Models\CashRegisterSession;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $superAdmin;
    protected User $manager;
    protected User $cashier;
    protected User $warehouse;
    protected User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store1 = Store::create(['name' => 'Store 1', 'code' => 'ST-01', 'is_active' => true]);
        $this->store2 = Store::create(['name' => 'Store 2', 'code' => 'ST-02', 'is_active' => true]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin', 'email' => 'admin@pos.test', 'password' => bcrypt('password'),
            'role' => 'super_admin', 'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Store Manager', 'email' => 'mgr@pos.test', 'password' => bcrypt('password'),
            'role' => 'store_manager', 'store_id' => $this->store1->id, 'is_active' => true,
        ]);

        $this->cashier = User::create([
            'name' => 'POS Cashier', 'email' => 'cashier@pos.test', 'password' => bcrypt('password'),
            'role' => 'cashier', 'store_id' => $this->store1->id, 'is_active' => true,
        ]);

        $this->warehouse = User::create([
            'name' => 'Logistics Officer', 'email' => 'wh@pos.test', 'password' => bcrypt('password'),
            'role' => 'warehouse_staff', 'store_id' => $this->store1->id, 'is_active' => true,
        ]);

        $this->accountant = User::create([
            'name' => 'Accountant', 'email' => 'acc@pos.test', 'password' => bcrypt('password'),
            'role' => 'accountant', 'is_active' => true,
        ]);
    }

    public function test_super_admin_dashboard_renders_enterprise_consolidation(): void
    {
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Executive Enterprise Dashboard');
    }

    public function test_store_manager_dashboard_renders_scoped_store_data(): void
    {
        $this->actingAs($this->manager);
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($this->store1->name);
        $response->assertDontSee($this->store2->name);
    }

    public function test_cashier_dashboard_renders_pos_terminal_status(): void
    {
        \App\Models\DailyCashRegister::create([
            'store_id' => $this->store1->id,
            'business_date' => now()->format('Y-m-d'),
            'opened_by' => $this->cashier->id,
            'opened_at' => now(),
            'opening_cash' => 5000.00,
            'cash_sales_amount' => 0.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 5000.00,
            'status' => 'open',
        ]);

        $this->actingAs($this->cashier);
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Cashier POS Terminal Hub');
        $response->assertSee('5,000.00');
    }

    public function test_warehouse_staff_dashboard_renders_logistics_operations(): void
    {
        $this->actingAs($this->warehouse);
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Warehouse');
        $response->assertSee('Dispatch Operations');
    }

    public function test_accountant_dashboard_renders_financial_control_centre(): void
    {
        $this->actingAs($this->accountant);
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Accountant');
        $response->assertSee('Financial Control Centre');
    }
}
