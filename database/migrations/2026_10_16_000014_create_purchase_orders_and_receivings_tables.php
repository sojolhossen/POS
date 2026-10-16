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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 60)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('destination_store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('status', 40)->default('draft')->index(); // draft, submitted, approved, partially_received, completed, cancelled
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->decimal('additional_costs', 12, 2)->default(0.00); // transport, loading, handling
            $table->string('landed_cost_allocation_basis', 40)->default('proportional_value'); // proportional_value, equal_unit
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('ordered_quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('additional_unit_cost', 12, 2)->default(0.00);
            $table->decimal('landed_unit_cost', 12, 2)->default(0.00);
            $table->integer('received_quantity')->default(0);
            $table->integer('accepted_quantity')->default(0);
            $table->integer('shortage_quantity')->default(0);
            $table->integer('excess_quantity')->default(0);
            $table->integer('damaged_quantity')->default(0);
            $table->timestamps();

            $table->unique(['purchase_order_id', 'product_variant_id'], 'po_variant_unique');
        });

        Schema::create('purchase_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number', 60)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('supplier_bill_number', 100)->nullable();
            $table->decimal('bill_amount', 14, 2)->default(0.00);
            $table->decimal('additional_costs', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('received_at');
            $table->timestamps();
        });

        Schema::create('purchase_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receiving_id')->constrained('purchase_receivings')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('scanned_barcode', 50)->nullable();
            $table->integer('quantity_received');
            $table->integer('quantity_accepted');
            $table->integer('quantity_shortage')->default(0);
            $table->integer('quantity_excess')->default(0);
            $table->integer('quantity_damaged')->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('allocated_additional_cost', 12, 2)->default(0.00);
            $table->decimal('landed_unit_cost', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receiving_items');
        Schema::dropIfExists('purchase_receivings');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
