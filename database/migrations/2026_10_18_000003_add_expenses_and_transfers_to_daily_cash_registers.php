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
        Schema::table('daily_cash_registers', function (Blueprint $table) {
            $table->decimal('cash_expenses_amount', 12, 2)->default(0.00)->after('cash_refunds_amount');
            $table->decimal('cash_transfers_amount', 12, 2)->default(0.00)->after('cash_expenses_amount');
            $table->decimal('opening_discrepancy_amount', 12, 2)->default(0.00)->after('opening_cash');
            $table->text('opening_discrepancy_reason')->nullable()->after('opening_discrepancy_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_cash_registers', function (Blueprint $table) {
            $table->dropColumn([
                'cash_expenses_amount',
                'cash_transfers_amount',
                'opening_discrepancy_amount',
                'opening_discrepancy_reason',
            ]);
        });
    }
};
