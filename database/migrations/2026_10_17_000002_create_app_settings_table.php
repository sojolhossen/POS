<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Seed default tax settings
        DB::table('app_settings')->insert([
            [
                'key' => 'vat_enabled',
                'value' => '1',
                'group' => 'tax',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'vat_rate',
                'value' => '5.00',
                'group' => 'tax',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'vat_label',
                'value' => 'Tax / VAT',
                'group' => 'tax',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
