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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('expense_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->string('role', 40); // store_manager, accountant, super_admin
            $table->decimal('max_amount', 14, 2); // expenses <= max_amount can be approved by this role
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 60)->unique();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('expense_date')->index();
            $table->string('payment_method', 30)->default('cash'); // cash, bank_transfer, bkash, nagad, card, pending
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('receipt_attachment_path', 255)->nullable();
            $table->string('status', 30)->default('pending_approval')->index(); // pending_approval, approved, rejected, paid
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'expense_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_approval_rules');
        Schema::dropIfExists('expense_categories');
    }
};
