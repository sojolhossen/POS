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
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 60)->unique();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_phone', 50)->nullable();
            
            // Financials (Decimal fixed-precision)
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->decimal('tax_rate', 5, 2)->default(0.00); // e.g. 5.00 for 5% VAT
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('rounding_adjustment', 6, 2)->default(0.00);
            $table->decimal('total_payable', 12, 2); // rounded whole BDT
            $table->decimal('total_paid', 12, 2);
            $table->decimal('change_returned', 12, 2)->default(0.00);
            
            // Loyalty points
            $table->integer('loyalty_points_earned')->default(0);
            $table->integer('loyalty_points_redeemed')->default(0);
            $table->decimal('loyalty_discount_amount', 12, 2)->default(0.00);

            // Status and Idempotency
            $table->string('status', 30)->default('completed')->index(); // completed, void, refunded, partially_refunded
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });

        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('barcode', 50)->index();
            $table->string('product_name', 150);
            $table->string('size', 50);
            $table->string('color', 50);
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2);
            $table->integer('quantity_returned')->default(0);
            $table->timestamps();
        });

        Schema::create('pos_sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->string('payment_method', 30); // cash, bkash, nagad, rocket, card
            $table->decimal('amount', 12, 2); // net amount applied to sale
            $table->decimal('tendered_amount', 12, 2);
            $table->decimal('change_amount', 12, 2)->default(0.00);
            $table->string('card_last_four', 4)->nullable();
            $table->string('transaction_reference', 100)->nullable(); // e.g. bKash TrxID or Card Slip
            $table->string('status', 30)->default('settled'); // settled, refunded, reversed
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sale_payments');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
    }
};
