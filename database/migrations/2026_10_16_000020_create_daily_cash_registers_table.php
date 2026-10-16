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
        Schema::create('daily_cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('business_date');
            $table->decimal('opening_cash', 12, 2);
            $table->foreignId('opened_by')->constrained('users');
            $table->timestamp('opened_at');
            $table->decimal('cash_sales_amount', 12, 2)->default(0.00);
            $table->decimal('cash_refunds_amount', 12, 2)->default(0.00);
            $table->decimal('cash_in_hand_expected', 12, 2)->default(0.00);
            $table->decimal('closing_cash_counted', 12, 2)->nullable();
            $table->decimal('discrepancy_amount', 12, 2)->default(0.00);
            $table->text('discrepancy_reason')->nullable();
            $table->string('status', 30)->default('open')->index(); // open, closed, reopened
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'business_date'], 'store_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_cash_registers');
    }
};
