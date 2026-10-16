<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\User;
use App\Services\FinancialAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsAndFinancialTransfersTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $accountant;
    protected Store $store;
    protected FinancialAccountService $accountService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Mirpur Retail Hub',
            'code' => 'STORE-01',
            'address' => 'Mirpur-10, Dhaka',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pos.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->accountant = User::create([
            'name' => 'Store Accountant',
            'email' => 'accountant@pos.test',
            'password' => bcrypt('password'),
            'role' => 'accountant',
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->accountService = app(FinancialAccountService::class);
    }

    public function test_can_create_financial_accounts_with_opening_balance(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('financial-accounts.store'), [
            'name' => 'BRAC Bank Principal',
            'account_type' => 'bank',
            'account_number' => '1501203948501001',
            'bank_name' => 'BRAC Bank PLC',
            'branch_name' => 'Gulshan',
            'opening_balance' => 500000.00,
        ]);

        $response->assertRedirect(route('financial-accounts.index'));
        $this->assertDatabaseHas('financial_accounts', [
            'name' => 'BRAC Bank Principal',
            'opening_balance' => 500000.00,
            'current_balance' => 500000.00,
        ]);

        $this->assertDatabaseHas('financial_account_transactions', [
            'transaction_type' => 'deposit',
            'amount' => 500000.00,
            'description' => 'Opening balance initialization',
        ]);
    }

    public function test_atomic_inter_account_transfer_updates_balances_without_revenue_double_counting(): void
    {
        $this->actingAs($this->accountant);

        $bank = FinancialAccount::create([
            'name' => 'City Bank Corporate',
            'account_type' => 'bank',
            'opening_balance' => 200000.00,
            'current_balance' => 200000.00,
            'is_active' => true,
        ]);

        $bkash = FinancialAccount::create([
            'name' => 'bKash Merchant Wallet',
            'account_type' => 'bkash',
            'account_number' => '01711000000',
            'opening_balance' => 10000.00,
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);

        $response = $this->post(route('financial-accounts.transfer'), [
            'source_account_id' => $bank->id,
            'destination_account_id' => $bkash->id,
            'amount' => 50000.00,
            'fee_amount' => 150.00,
            'notes' => 'Disbursement to merchant wallet for petty payroll',
        ]);

        $response->assertRedirect(route('financial-accounts.index'));

        // City Bank: 200,000 - 50,000 - 150 (fee) = 149,850.00
        $this->assertEquals(149850.00, (float) $bank->fresh()->current_balance);
        // bKash: 10,000 + 50,000 = 60,000.00
        $this->assertEquals(60000.00, (float) $bkash->fresh()->current_balance);

        // Fee must be recorded as separate fee transaction
        $this->assertDatabaseHas('financial_account_transactions', [
            'financial_account_id' => $bank->id,
            'transaction_type' => 'fee',
            'amount' => -150.00,
        ]);
    }

    public function test_cannot_transfer_with_insufficient_balance(): void
    {
        $this->actingAs($this->accountant);

        $bank = FinancialAccount::create([
            'name' => 'Dutch Bangla Bank',
            'account_type' => 'bank',
            'opening_balance' => 5000.00,
            'current_balance' => 5000.00,
            'is_active' => true,
        ]);

        $nagad = FinancialAccount::create([
            'name' => 'Nagad Business',
            'account_type' => 'nagad',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $response = $this->post(route('financial-accounts.transfer'), [
            'source_account_id' => $bank->id,
            'destination_account_id' => $nagad->id,
            'amount' => 20000.00,
            'fee_amount' => 0.00,
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(5000.00, (float) $bank->fresh()->current_balance);
        $this->assertEquals(0.00, (float) $nagad->fresh()->current_balance);
    }
}
