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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 50)->unique();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('total_spend', 14, 2)->default(0.00);
            $table->integer('loyalty_points')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_point_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('event_type', 30); // earned, redeemed, reversed, adjustment
            $table->integer('points'); // positive for earned, negative for redeemed/reversed
            $table->integer('balance_after');
            $table->string('reference_type', 50)->nullable(); // e.g. App\Models\PosSale, App\Models\PosReturn
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_ledgers');
        Schema::dropIfExists('customers');
    }
};
