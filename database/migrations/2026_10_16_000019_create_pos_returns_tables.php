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
        Schema::create('pos_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 60)->unique();
            $table->foreignId('original_pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->foreignId('replacement_pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('processed_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // required for receiptless returns
            $table->boolean('is_receiptless')->default(false);
            $table->string('return_type', 30); // refund, exchange
            $table->decimal('total_returned_value', 12, 2);
            $table->decimal('replacement_value', 12, 2)->default(0.00);
            $table->decimal('net_settlement_amount', 12, 2); // negative = cash/refund to customer; positive = customer pays difference
            $table->string('settlement_method', 30); // cash, bkash, nagad, card, exchange
            $table->string('refund_reference', 100)->nullable();
            $table->text('reason');
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });

        Schema::create('pos_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_return_id')->constrained('pos_returns')->cascadeOnDelete();
            $table->foreignId('original_pos_sale_item_id')->constrained('pos_sale_items')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('condition', 30)->default('sellable'); // sellable, damaged, defective
            $table->string('reason_description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_return_items');
        Schema::dropIfExists('pos_returns');
    }
};
