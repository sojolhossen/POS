<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormattingHelper
{
    /**
     * Format a monetary value to BDT currency string (e.g. ৳ 1,250.00).
     */
    public static function currency(float|int|string|null $amount): string
    {
        $num = (float) ($amount ?? 0);
        return '৳ ' . number_format($num, 2, '.', ',');
    }

    /**
     * Format a date to DD-MM-YYYY (e.g. 16-10-2026).
     */
    public static function date(mixed $date, string $format = 'd-m-Y'): string
    {
        if (!$date) {
            return '-';
        }

        if ($date instanceof Carbon) {
            return $date->format($format);
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (\Throwable) {
            return (string) $date;
        }
    }
}
