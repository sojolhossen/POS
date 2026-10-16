<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('name', 120);
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('designation', 100);
            $table->string('employment_status', 30)->default('active')->index(); // active, on_leave, terminated
            $table->date('joining_date');
            $table->decimal('monthly_salary', 12, 2)->default(0.00);
            $table->decimal('current_advance_balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('status', 30)->default('present'); // present, absent, late, half_day, leave
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->text('notes')->nullable();
            $table->text('correction_reason')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index(['store_id', 'date']);
        });

        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->string('advance_number', 60)->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('request_date')->index();
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('payment_method', 30)->default('cash');
            $table->string('status', 30)->default('pending')->index(); // pending, approved, paid, repaid
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 60)->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('salary_month', 7)->index(); // YYYY-MM format
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('advance_deduction', 12, 2)->default(0.00);
            $table->decimal('net_payable', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('outstanding_balance', 12, 2)->default(0.00);
            $table->string('payment_method', 30)->default('bank_transfer'); // bank_transfer, cash, bkash
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->string('status', 30)->default('paid')->index(); // partial, paid
            $table->date('payment_date');
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'salary_month'], 'emp_month_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
    }
};
