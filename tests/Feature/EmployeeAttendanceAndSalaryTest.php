<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAttendanceAndSalaryTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $superAdmin;
    protected User $manager1;
    protected User $manager2;
    protected User $accountant;
    protected Employee $employee;
    protected FinancialAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store1 = Store::create(['name' => 'Store 1', 'code' => 'ST-01', 'is_active' => true]);
        $this->store2 = Store::create(['name' => 'Store 2', 'code' => 'ST-02', 'is_active' => true]);

        $this->superAdmin = User::create([
            'name' => 'Admin', 'email' => 'admin@pos.test', 'password' => bcrypt('password'),
            'role' => 'super_admin', 'is_active' => true,
        ]);

        $this->manager1 = User::create([
            'name' => 'Manager 1', 'email' => 'mgr1@pos.test', 'password' => bcrypt('password'),
            'role' => 'store_manager', 'store_id' => $this->store1->id, 'is_active' => true,
        ]);

        $this->manager2 = User::create([
            'name' => 'Manager 2', 'email' => 'mgr2@pos.test', 'password' => bcrypt('password'),
            'role' => 'store_manager', 'store_id' => $this->store2->id, 'is_active' => true,
        ]);

        $this->accountant = User::create([
            'name' => 'Accountant', 'email' => 'acc@pos.test', 'password' => bcrypt('password'),
            'role' => 'accountant', 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'name' => 'Shakil Ahmed',
            'employee_code' => 'EMP-1001',
            'store_id' => $this->store1->id,
            'designation' => 'Senior Sales Executive',
            'phone' => '01700112233',
            'employment_status' => 'active',
            'joining_date' => '2025-01-01',
            'monthly_salary' => 25000.00,
            'current_advance_balance' => 0.00,
        ]);

        $this->bankAccount = FinancialAccount::create([
            'name' => 'City Bank Corporate',
            'account_type' => 'bank',
            'opening_balance' => 100000.00,
            'current_balance' => 100000.00,
            'is_active' => true,
        ]);
    }

    public function test_store_manager_cannot_view_employees_from_another_store(): void
    {
        $this->actingAs($this->manager2);

        $response = $this->get(route('employees.show', $this->employee));
        $response->assertStatus(403);
    }

    public function test_can_record_daily_attendance_and_updates_audit_trail(): void
    {
        $this->actingAs($this->manager1);

        $response = $this->post(route('attendance.store'), [
            'employee_id' => $this->employee->id,
            'store_id' => $this->store1->id,
            'attendance_date' => '2026-10-16',
            'status' => 'present',
            'check_in' => '09:05',
            'check_out' => '18:15',
            'notes' => 'Punctual attendance',
        ]);

        $response->assertRedirect(route('attendance.index', ['date' => '2026-10-16', 'store_id' => $this->store1->id]));
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-10-16',
            'status' => 'present',
            'recorded_by' => $this->manager1->id,
        ]);
    }

    public function test_salary_advance_and_monthly_payroll_reconciliation(): void
    {
        $this->actingAs($this->accountant);

        // 1. Disburse advance of 5,000 BDT
        $advanceResponse = $this->post(route('payroll.advance'), [
            'employee_id' => $this->employee->id,
            'amount' => 5000.00,
            'payment_method' => 'bank',
            'financial_account_id' => $this->bankAccount->id,
            'reason' => 'Family festival advance',
        ]);

        $advanceResponse->assertRedirect(route('payroll.index'));
        $this->assertDatabaseHas('salary_advances', [
            'employee_id' => $this->employee->id,
            'amount' => 5000.00,
            'status' => 'paid',
        ]);

        // Employee advance balance updated
        $this->assertEquals(5000.00, (float) $this->employee->fresh()->current_advance_balance);

        // Bank balance decreased: 100,000 - 5,000 = 95,000.00
        $this->assertEquals(95000.00, (float) $this->bankAccount->fresh()->current_balance);

        // 2. Disburse October 2026 Salary
        // Base: 25,000. Advance deduction: 5,000. Net to pay = 20,000.00
        $payResponse = $this->post(route('payroll.pay'), [
            'employee_id' => $this->employee->id,
            'salary_month' => '2026-10',
            'amount_to_pay' => 20000.00,
            'payment_method' => 'bank',
            'financial_account_id' => $this->bankAccount->id,
            'notes' => 'October 2026 monthly payroll',
        ]);

        $payResponse->assertRedirect(route('payroll.index'));

        $this->assertDatabaseHas('salary_payments', [
            'employee_id' => $this->employee->id,
            'salary_month' => '2026-10',
            'basic_salary' => 25000.00,
            'advance_deduction' => 5000.00,
            'net_payable' => 20000.00,
            'amount_paid' => 20000.00,
            'status' => 'paid',
        ]);

        // Advance balance reconciled to 0
        $this->assertEquals(0.00, (float) $this->employee->fresh()->current_advance_balance);

        // Bank balance decreased by net 20,000: 95,000 - 20,000 = 75,000.00
        $this->assertEquals(75000.00, (float) $this->bankAccount->fresh()->current_balance);
    }

    public function test_prevents_duplicate_monthly_salary_payment(): void
    {
        $this->actingAs($this->accountant);

        // First payment
        $this->post(route('payroll.pay'), [
            'employee_id' => $this->employee->id,
            'salary_month' => '2026-09',
            'amount_to_pay' => 25000.00,
            'payment_method' => 'bank',
            'financial_account_id' => $this->bankAccount->id,
        ]);

        // Duplicate attempt
        $duplicate = $this->post(route('payroll.pay'), [
            'employee_id' => $this->employee->id,
            'salary_month' => '2026-09',
            'amount_to_pay' => 25000.00,
            'payment_method' => 'bank',
            'financial_account_id' => $this->bankAccount->id,
        ]);

        $duplicate->assertSessionHasErrors();
    }

    public function test_attendance_index_page_loads_with_attendances_and_pagination(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertViewHas('attendances');
        $response->assertViewHas('storeId');
        $response->assertSee('Store Staff Attendance');
    }
}
