<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SkuGeneratorService
{
    /**
     * Generate a unique SKU using Category Code and a 6-digit serial (e.g. SH-000123).
     * Concurrency-safe using database transactions and row locks.
     */
    public static function generate(Category $category): string
    {
        $code = strtoupper(trim($category->code));
        if (empty($code)) {
            $code = strtoupper(substr($category->name, 0, 2));
        }

        return DB::transaction(function () use ($code) {
            // Find all SKUs matching this prefix, lock for update
            $lastProduct = Product::where('sku', 'LIKE', "{$code}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextSerial = 1;
            if ($lastProduct) {
                // Extract numeric serial from SKU (e.g. SH-000123 -> 123)
                if (preg_match('/' . preg_quote($code, '/') . '-(\d+)/', $lastProduct->sku, $matches)) {
                    $nextSerial = ((int) $matches[1]) + 1;
                }
            }

            // Ensure uniqueness
            do {
                $sku = sprintf('%s-%06d', $code, $nextSerial);
                $exists = Product::where('sku', $sku)->exists();
                if ($exists) {
                    $nextSerial++;
                }
            } while ($exists);

            return $sku;
        });
    }

    /**
     * Auto-generate a store code (e.g. STR-001 or WH-001)
     */
    public static function generateStoreCode(bool $isWarehouse = false): string
    {
        $prefix = $isWarehouse ? 'WH' : 'STR';

        return DB::transaction(function () use ($prefix) {
            $lastStore = DB::table('stores')
                ->where('code', 'LIKE', "{$prefix}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextSerial = 1;
            if ($lastStore && preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/', $lastStore->code, $matches)) {
                $nextSerial = ((int) $matches[1]) + 1;
            }

            do {
                $code = sprintf('%s-%03d', $prefix, $nextSerial);
                $exists = DB::table('stores')->where('code', $code)->exists();
                if ($exists) {
                    $nextSerial++;
                }
            } while ($exists);

            return $code;
        });
    }
}
