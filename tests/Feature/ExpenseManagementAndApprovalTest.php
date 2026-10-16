<?php

namespace Tests\Feature;

use App\Models\CashRegisterSession;
use App\Models\Expense;
use App\Models\ExpenseApprovalRule;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementAndApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $superAdmin;
    protected User $storeManager;
    protected User $accountant;
    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Dhanmondi Outlet',
            'code' => 'STORE-DHN',
            'address' => 'Dhanmondi 27',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pos.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->storeManager = User::create([
            'name' => 'Dhanmondi Manager',
            'email' => 'manager@pos.test',
            'password' => bcrypt('password'),
            'role' => 'store_manager',
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->accountant = User::create([
            'name' => 'Head Accountant',
            'email' => 'accountant@pos.test',
            'password' => bcrypt('password'),
            'role' => 'accountant',
            'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'name' => 'Store Utilities',
            'code' => 'UTIL-01',
            'is_active' => true,
        ]);

        // Configurable threshold rule: expenses <= 5000 can be approved by store_manager
        ExpenseApprovalRule::create([
            'role' => 'store_manager',
            'max_amount' => 5000.00,
            'store_id' => $this->store->id,
        ]);
    }

    public function test_expense_under_threshold_is_auto_approved(): void
    {
        $this->actingAs($this->storeManager);

        $response = $this->post(route('expenses.store'), [
            'store_id' => $this->store->id,
            'expense_category_id' => $this->category->id,
            'amount' => 1200.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'description' => 'Lightbulb and emergency electrical repairs',
        ]);

        $response->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $this->category->id,
            'amount' => 1200.00,
            'status' => 'approved',
        ]);
    }

    public function test_large_expense_requires_approval_and_prevents_self_approval(): void
    {
        $this->actingAs($this->storeManager);

        // Record a 15,000 BDT generator maintenance expense (exceeds store manager limit of 5,000)
        $response = $this->post(route('expenses.store'), [
            'store_id' => $this->store->id,
            'expense_category_id' => $this->category->id,
            'amount' => 15000.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'bank',
            'description' => 'Generator diesel and complete overhaul servicing',
        ]);

        $response->assertRedirect(route('expenses.index'));

        $expense = Expense::where('amount', 15000.00)->first();
        $this->assertNotNull($expense);
        $this->assertEquals('pending_approval', $expense->status);

        // Store manager attempts to approve own restricted expense -> Forbidden
        $approveAttempt = $this->post(route('expenses.approve', $expense));
        $approveAttempt->assertStatus(403);
        $this->assertEquals('pending_approval', $expense->fresh()->status);

        // Accountant can approve
        $this->actingAs($this->accountant);
        $accountantApprove = $this->post(route('expenses.approve', $expense));
        $accountantApprove->assertRedirect(route('expenses.index'));
        $this->assertEquals('approved', $expense->fresh()->status);
        $this->assertEquals($this->accountant->id, $expense->fresh()->approved_by);
    }

    public function test_cash_expense_payment_updates_cash_register_drawer(): void
    {
        // Open active cash register
        $register = \App\Models\DailyCashRegister::create([
            'store_id' => $this->store->id,
            'business_date' => now()->toDateString(),
            'opening_cash' => 10000.00,
            'opened_by' => $this->storeManager->id,
            'opened_at' => now(),
            'cash_sales_amount' => 0.00,
            'cash_refunds_amount' => 0.00,
            'cash_in_hand_expected' => 10000.00,
            'status' => 'open',
        ]);

        $this->actingAs($this->storeManager);

        $this->post(route('expenses.store'), [
            'store_id' => $this->store->id,
            'expense_category_id' => $this->category->id,
            'amount' => 2500.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'description' => 'Tea and hospitality for store guests',
        ]);

        // Cash drawer should decrement from 10,000 to 7,500
        $this->assertEquals(7500.00, (float) $register->fresh()->cash_in_hand_expected);
    }
}
