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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('report_type', 50)->index(); // sales_summary, profit_loss, inventory_valuation, expense_breakdown, cash_reconciliation
            $table->string('frequency', 20)->default('daily')->index(); // daily, weekly, monthly
            $table->json('recipients'); // Array of email addresses
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('format', 10)->default('pdf'); // pdf, csv
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('status', 20)->default('idle'); // idle, running, failed, completed
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->string('import_batch_id', 60)->unique();
            $table->string('dataset_type', 40)->index(); // products, opening_stock, suppliers, customers, employees
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->string('status', 30)->default('pending_preview')->index(); // pending_preview, validated, confirmed, completed, failed
            $table->integer('total_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->json('validation_errors')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_imports');
        Schema::dropIfExists('scheduled_reports');
    }
};
