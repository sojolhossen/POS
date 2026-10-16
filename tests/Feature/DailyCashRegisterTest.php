<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DailyCashRegister;
use App\Models\Store;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $cashier;
    protected User $storeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Mirpur Branch',
            'code' => 'MRP-01',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::CASHIER,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->storeManager = User::factory()->create([
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test opening daily register, tracking movements, and closing with discrepancy check.
     */
    public function test_daily_register_lifecycle_and_discrepancy_controls(): void
    {
        $cashService = app(CashRegisterService::class);

        // 1. Open morning register with 3000 float
        $register = $cashService->openRegister($this->store, $this->cashier, 3000.00);
        $this->assertEquals(3000.00, (float) $register->opening_cash);
        $this->assertEquals('open', $register->status);
        $this->assertEquals(3000.00, (float) $register->expected_closing_cash);

        // 2. Track 1500 cash sales and 200 cash refunds
        $cashService->recordCashSale($this->store, 1500.00);
        $cashService->recordCashRefund($this->store, 200.00);

        $register->refresh();
        $this->assertEquals(1500.00, (float) $register->cash_sales);
        $this->assertEquals(200.00, (float) $register->cash_refunds);
        // Expected = 3000 + 1500 - 200 = 4300.00
        $this->assertEquals(4300.00, (float) $register->expected_closing_cash);

        // 3. Evening closing: Counted 4250 (50 short). Discrepancy reason is mandatory!
        $this->expectException(\Exception::class);
        $cashService->closeRegister(
            $register,
            $this->cashier,
            4250.00,
            null // missing reason must throw exception!
        );
    }

    /**
     * Test closing with valid discrepancy reason and reopening with manager audit.
     */
    public function test_close_with_discrepancy_reason_and_reopen(): void
    {
        $cashService = app(CashRegisterService::class);
        $register = $cashService->openRegister($this->store, $this->cashier, 2000.00);

        $cashService->recordCashSale($this->store, 1000.00);
        $register->refresh();

        // Close with 2950 counted (-50 discrepancy) and valid explanation
        $closedRegister = $cashService->closeRegister(
            $register,
            $this->cashier,
            2950.00,
            '৳50 coin change shortage'
        );

        $this->assertEquals('closed', $closedRegister->status);
        $this->assertEquals(-50.00, (float) $closedRegister->cash_discrepancy);
        $this->assertEquals(2950.00, (float) $closedRegister->closing_cash_counted);

        // Manager reopens day for late authorized transaction
        $reopenedRegister = $cashService->reopenRegister(
            $closedRegister,
            $this->storeManager,
            'Late customer exchange transaction permitted by head office'
        );

        $this->assertTrue($reopenedRegister->isOpen());
        $this->assertNotNull($reopenedRegister->reopened_at);
        $this->assertEquals($this->storeManager->id, $reopenedRegister->reopened_by);
    }
}
