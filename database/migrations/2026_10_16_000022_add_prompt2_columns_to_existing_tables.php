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
        if (!Schema::hasColumn('product_variants', 'weighted_average_cost')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->decimal('weighted_average_cost', 12, 2)->default(0.00)->after('purchase_price');
            });
        }

        if (!Schema::hasColumn('stock_balances', 'damaged_quantity')) {
            Schema::table('stock_balances', function (Blueprint $table) {
                $table->integer('damaged_quantity')->default(0)->after('quantity_reserved');
            });
        }

        if (!Schema::hasColumn('stock_transfers', 'approved_by')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stock_transfers', 'approved_by')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approved_by', 'approved_at']);
            });
        }

        if (Schema::hasColumn('stock_balances', 'damaged_quantity')) {
            Schema::table('stock_balances', function (Blueprint $table) {
                $table->dropColumn('damaged_quantity');
            });
        }

        if (Schema::hasColumn('product_variants', 'weighted_average_cost')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('weighted_average_cost');
            });
        }
    }
};
