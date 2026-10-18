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
        Schema::create('store_cash_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('daily_cash_register_id')->nullable()->constrained('daily_cash_registers')->nullOnDelete();
            $table->string('transfer_number', 60)->unique();
            $table->decimal('amount', 14, 2);
            $table->string('transfer_method', 40)->default('cash_to_owner'); // cash_to_owner, bank_deposit, bkash, nagad, rocket, other
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('recipient_name', 150)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending, approved, rejected
            $table->foreignId('transferred_by')->constrained('users');
            $table->timestamp('transferred_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_cash_transfers');
    }
};
