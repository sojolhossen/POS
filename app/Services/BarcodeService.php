<?php

namespace App\Services;

use App\Models\ProductVariant;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Illuminate\Support\Facades\DB;

class BarcodeService
{
    /**
     * Generate a unique 12-digit Code 128 barcode string.
     * Uses prefix '880' (Bangladesh standard code) followed by 8 unique digits and a checksum.
     */
    public static function generateUniqueBarcode(): string
    {
        return DB::transaction(function () {
            do {
                // 880 prefix + 8 random digits
                $base = '880' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                // Calculate simple modulo-10 Luhn check digit
                $checkDigit = self::calculateCheckDigit($base);
                $barcode = $base . $checkDigit;

                $exists = ProductVariant::where('barcode', $barcode)->exists();
            } while ($exists);

            return $barcode;
        });
    }

    /**
     * Render Code 128 SVG representation for a given barcode string.
     */
    public static function renderSvg(string $barcode, int $widthFactor = 2, int $totalHeight = 40): string
    {
        $generator = new BarcodeGeneratorSVG();
        $svg = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, $widthFactor, $totalHeight);
        // Strip standalone XML declaration and DOCTYPE for clean inline HTML5 SVG embedding
        $svg = preg_replace('/<\?xml.*?\?>/s', '', $svg);
        $svg = preg_replace('/<!DOCTYPE.*?>/s', '', $svg);
        return trim($svg);
    }

    /**
     * Luhn modulo 10 check digit calculation.
     */
    protected static function calculateCheckDigit(string $number): int
    {
        $sum = 0;
        $len = strlen($number);
        for ($i = 0; $i < $len; $i++) {
            $digit = (int) $number[$len - 1 - $i];
            if ($i % 2 === 0) {
                $digit *= 3;
            }
            $sum += $digit;
        }
        $remainder = $sum % 10;
        return $remainder === 0 ? 0 : 10 - $remainder;
    }
}
