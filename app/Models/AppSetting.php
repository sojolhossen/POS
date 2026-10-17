<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Get a setting by key with optional default.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting !== null ? $setting->value : $default;
    }

    /**
     * Set / update a setting by key.
     */
    public static function set(string $key, $value, string $group = 'general'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'group' => $group]
        );
    }

    /**
     * Check if VAT / Tax is enabled system-wide.
     */
    public static function isVatEnabled(): bool
    {
        $val = static::get('vat_enabled', '1');
        return in_array((string) $val, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Get effective VAT percentage.
     * Returns 0.00 if VAT is turned off.
     */
    public static function getVatRate(): float
    {
        if (!static::isVatEnabled()) {
            return 0.00;
        }

        return (float) static::get('vat_rate', 5.00);
    }

    /**
     * Get custom VAT / Tax label.
     */
    public static function getVatLabel(): string
    {
        return (string) static::get('vat_label', 'Tax / VAT');
    }

    /**
     * Get Application / Site Name.
     */
    public static function getAppName(string $default = 'FashionPOS'): string
    {
        $name = static::get('app_name');
        return !empty($name) ? (string) $name : $default;
    }

    /**
     * Get Application Tagline / Slogan.
     */
    public static function getAppTagline(string $default = 'Smart Retail POS & ERP System'): string
    {
        $tagline = static::get('app_tagline');
        return !empty($tagline) ? (string) $tagline : $default;
    }

    /**
     * Get Application Logo URL.
     * Returns null if no logo uploaded.
     */
    public static function getAppLogo(): ?string
    {
        $path = static::get('app_logo');
        if (empty($path)) {
            return null;
        }

        // If path is an external URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Get Application Favicon URL.
     */
    public static function getAppFavicon(): ?string
    {
        $path = static::get('app_favicon');
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Get Company Address.
     */
    public static function getCompanyAddress(string $default = 'Level 3, Bashundhara City, Dhaka'): string
    {
        $val = static::get('company_address');
        return !empty($val) ? (string) $val : $default;
    }

    /**
     * Get Company Contact Phone.
     */
    public static function getCompanyPhone(string $default = '01700-000000'): string
    {
        $val = static::get('company_phone');
        return !empty($val) ? (string) $val : $default;
    }

    /**
     * Get Company Email.
     */
    public static function getCompanyEmail(string $default = 'support@fashionpos.bd'): string
    {
        $val = static::get('company_email');
        return !empty($val) ? (string) $val : $default;
    }

    /**
     * Get Receipt / Invoice Footer Text.
     */
    public static function getReceiptFooter(string $default = 'Thank you for shopping with us!'): string
    {
        $val = static::get('receipt_footer');
        return !empty($val) ? (string) $val : $default;
    }
}
