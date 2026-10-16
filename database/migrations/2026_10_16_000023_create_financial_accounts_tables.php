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
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('account_type', 40)->index(); // cash, bank, bkash, nagad, rocket, card_gateway, supplier_liability
            $table->string('account_number', 80)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('branch_name', 100)->nullable();
            $table->string('routing_number', 50)->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->string('currency', 10)->default('BDT');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('financial_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->string('transaction_type', 40)->index(); // deposit, withdrawal, transfer_in, transfer_out, pos_settlement, supplier_payment, expense_payment, salary_payment, refund_payout
            $table->decimal('amount', 14, 2);
            $table->decimal('fee_amount', 10, 2)->default(0.00);
            $table->decimal('balance_after', 14, 2);
            $table->foreignId('related_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 100)->nullable()->index();
            $table->string('description', 255)->nullable();
            $table->string('reconciliation_status', 20)->default('unreconciled')->index(); // unreconciled, reconciled, discrepancy
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index(['financial_account_id', 'created_at'], 'fin_tx_acc_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_account_transactions');
        Schema::dropIfExists('financial_accounts');
    }
};
