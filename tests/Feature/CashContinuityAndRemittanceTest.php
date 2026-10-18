<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DailyCashRegister;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\StoreCashTransfer;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\StoreBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashContinuityAndRemittanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Dhanmondi Branch',
            'code' => 'DHK01',
            'is_active' => true,
            'is_warehouse' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => UserRole::SUPER_ADMIN,
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Store Manager',
            'email' => 'manager@example.com',
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $this->store->id,
        ]);
        $this->store->update(['manager_id' => $this->manager->id]);
    }

    public function test_first_time_store_opening_sets_initial_cash_and_sales_accumulate()
    {
        $service = app(CashRegisterService::class);
        $balanceService = app(StoreBalanceService::class);

        // 1. Initial opening with 3,000 ৳
        $register = $service->openRegister($this->store, $this->manager, 3000.00);

        $this->assertEquals(3000.00, (float) $register->opening_cash);
        $this->assertEquals(3000.00, (float) $register->cash_in_hand_expected);

        // Check live balance
        $balanceData = $balanceService->getHeaderBalanceData($this->manager);
        $this->assertEquals(3000.00, (float) $balanceData['liveBalance']);

        // 2. Sales of 10,000 ৳ in cash
        $service->recordCashSale($this->store, 10000.00);
        $register->refresh();

        $this->assertEquals(10000.00, (float) $register->cash_sales_amount);
        $this->assertEquals(13000.00, (float) $register->cash_in_hand_expected);

        $balanceData = $balanceService->getHeaderBalanceData($this->manager);
        $this->assertEquals(13000.00, (float) $balanceData['liveBalance']);
    }

    public function test_manager_cash_transfer_to_owner_immediately_deducts_drawer_balance()
    {
        $service = app(CashRegisterService::class);
        $register = $service->openRegister($this->store, $this->manager, 3000.00);
        $service->recordCashSale($this->store, 10000.00); // 13,000 total

        $this->actingAs($this->manager);

        // Remit 3,000 to owner
        $response = $this->post(route('daily-registers.transfer-cash'), [
            'store_id' => $this->store->id,
            'amount' => 3000.00,
            'transfer_method' => 'cash_to_owner',
            'recipient_name' => 'Store Owner',
            'reference_number' => 'HANDOVER-01',
            'notes' => 'Evening cash handover',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $register->refresh();
        $this->assertEquals(3000.00, (float) $register->cash_transfers_amount);
        $this->assertEquals(10000.00, (float) $register->cash_in_hand_expected);

        $transfer = StoreCashTransfer::where('store_id', $this->store->id)->first();
        $this->assertNotNull($transfer);
        $this->assertEquals(3000.00, (float) $transfer->amount);
        $this->assertEquals('pending', $transfer->status);

        // Live balance reflects deduction immediately
        $balanceService = app(StoreBalanceService::class);
        $balanceData = $balanceService->getHeaderBalanceData($this->manager);
        $this->assertEquals(10000.00, (float) $balanceData['liveBalance']);
    }

    public function test_admin_rejection_of_transfer_immediately_restores_cash_to_drawer()
    {
        $service = app(CashRegisterService::class);
        $register = $service->openRegister($this->store, $this->manager, 3000.00);
        $service->recordCashSale($this->store, 10000.00);

        // Transfer 3,000 out -> expected becomes 10,000
        $transfer = $service->transferCashToOwner(
            store: $this->store,
            user: $this->manager,
            amount: 3000.00,
            transferMethod: 'cash_to_owner'
        );

        $register->refresh();
        $this->assertEquals(10000.00, (float) $register->cash_in_hand_expected);

        // Admin rejects the transfer
        $this->actingAs($this->admin);
        $response = $this->post(route('daily-registers.transfers.reject', $transfer), [
            'rejection_reason' => 'Cash was not handed over to owner',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('rejected', $transfer->status);
        $this->assertEquals('Cash was not handed over to owner', $transfer->rejection_reason);

        // Cash is restored back to the register
        $register->refresh();
        $this->assertEquals(0.00, (float) $register->cash_transfers_amount);
        $this->assertEquals(13000.00, (float) $register->cash_in_hand_expected);

        // Live balance back to 13,000
        $balanceService = app(StoreBalanceService::class);
        $balanceData = $balanceService->getHeaderBalanceData($this->manager);
        $this->assertEquals(13000.00, (float) $balanceData['liveBalance']);
    }

    public function test_next_day_register_opening_suggests_previous_closing_balance()
    {
        $service = app(CashRegisterService::class);

        // Day 1
        $day1Register = $service->openRegister($this->store, $this->manager, 3000.00);
        $service->recordCashSale($this->store, 10000.00);
        $service->transferCashToOwner($this->store, $this->manager, 3000.00, 'cash_to_owner');
        $day1Register->refresh();
        // Register expected is now 10,000. Close day 1 with 10,000
        $service->closeRegister($day1Register, $this->manager, 10000.00);

        // Simulate next day
        $day1Register->update(['business_date' => now()->subDay()->format('Y-m-d')]);

        $suggested = $service->getSuggestedOpeningCash($this->store);
        $this->assertTrue($suggested['has_previous']);
        $this->assertEquals(10000.00, (float) $suggested['suggested_amount']);

        // Day 2 opening
        $day2Register = $service->openRegister($this->store, $this->manager, $suggested['suggested_amount']);
        $this->assertEquals(10000.00, (float) $day2Register->opening_cash);
        $this->assertEquals(0.00, (float) $day2Register->opening_discrepancy_amount);
        $this->assertEquals(10000.00, (float) $day2Register->cash_in_hand_expected);
    }

    public function test_admin_approval_of_cash_transfer_deposits_to_financial_account()
    {
        $financialAccount = FinancialAccount::create([
            'name' => 'Owner Main Cash Account',
            'account_type' => 'cash',
            'store_id' => $this->store->id,
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        $service = app(CashRegisterService::class);
        $service->openRegister($this->store, $this->manager, 5000.00);

        $transfer = $service->transferCashToOwner(
            store: $this->store,
            user: $this->manager,
            amount: 2000.00,
            transferMethod: 'cash_to_owner',
            financialAccountId: $financialAccount->id
        );

        $this->actingAs($this->admin);
        $response = $this->post(route('daily-registers.transfers.approve', $transfer));
        $response->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('approved', $transfer->status);

        $financialAccount->refresh();
        $this->assertEquals(2000.00, (float) $financialAccount->current_balance);
    }

    public function test_logging_petty_cash_expense_deducts_from_drawer_balance()
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Supplies & Tea',
            'code' => 'SUPPLIES_TEA',
            'is_active' => true,
        ]);

        $service = app(CashRegisterService::class);
        $register = $service->openRegister($this->store, $this->manager, 3000.00);

        $this->actingAs($this->manager);

        $response = $this->post(route('daily-registers.record-expense'), [
            'store_id' => $this->store->id,
            'expense_category_id' => $category->id,
            'amount' => 350.00,
            'title' => 'Evening tea and broom',
            'description' => 'Store maintenance supplies',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $register->refresh();
        $this->assertEquals(350.00, (float) $register->cash_expenses_amount);
        $this->assertEquals(2650.00, (float) $register->cash_in_hand_expected);
    }
}
