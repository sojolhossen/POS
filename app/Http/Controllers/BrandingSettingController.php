<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BrandingSettingController extends Controller
{
    /**
     * Show Site Branding & Identity Configuration (Super Admin Only).
     */
    public function index()
    {
        $appName = AppSetting::getAppName();
        $appTagline = AppSetting::getAppTagline();
        $appLogo = AppSetting::getAppLogo();
        $rawLogo = AppSetting::get('app_logo');
        $appFavicon = AppSetting::getAppFavicon();
        $rawFavicon = AppSetting::get('app_favicon');
        $companyAddress = AppSetting::getCompanyAddress();
        $companyPhone = AppSetting::getCompanyPhone();
        $companyEmail = AppSetting::getCompanyEmail();
        $receiptFooter = AppSetting::getReceiptFooter();

        return view('settings.branding', compact(
            'appName',
            'appTagline',
            'appLogo',
            'rawLogo',
            'appFavicon',
            'rawFavicon',
            'companyAddress',
            'companyPhone',
            'companyEmail',
            'receiptFooter'
        ));
    }

    /**
     * Update Site Branding & Identity Settings (Super Admin Only).
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'app_tagline' => ['nullable', 'string', 'max:200'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:100'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:3072'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'in:0,1,true,false'],
            'remove_favicon' => ['nullable', 'in:0,1,true,false'],
        ]);

        $oldValues = [
            'app_name' => AppSetting::get('app_name', 'FashionPOS'),
            'app_tagline' => AppSetting::get('app_tagline', 'Smart Retail POS & ERP System'),
            'app_logo' => AppSetting::get('app_logo'),
            'app_favicon' => AppSetting::get('app_favicon'),
            'company_address' => AppSetting::get('company_address'),
            'company_phone' => AppSetting::get('company_phone'),
            'company_email' => AppSetting::get('company_email'),
            'receipt_footer' => AppSetting::get('receipt_footer'),
        ];

        // 1. Text Fields
        $appName = trim($validated['app_name']);
        $appTagline = !empty($validated['app_tagline']) ? trim($validated['app_tagline']) : '';
        $companyAddress = !empty($validated['company_address']) ? trim($validated['company_address']) : '';
        $companyPhone = !empty($validated['company_phone']) ? trim($validated['company_phone']) : '';
        $companyEmail = !empty($validated['company_email']) ? trim($validated['company_email']) : '';
        $receiptFooter = !empty($validated['receipt_footer']) ? trim($validated['receipt_footer']) : '';

        AppSetting::set('app_name', $appName, 'branding');
        AppSetting::set('app_tagline', $appTagline, 'branding');
        AppSetting::set('company_address', $companyAddress, 'branding');
        AppSetting::set('company_phone', $companyPhone, 'branding');
        AppSetting::set('company_email', $companyEmail, 'branding');
        AppSetting::set('receipt_footer', $receiptFooter, 'branding');

        // 2. Logo Handling
        $removeLogo = filter_var($request->input('remove_logo'), FILTER_VALIDATE_BOOLEAN);
        $currentLogo = AppSetting::get('app_logo');

        if ($removeLogo && empty($request->hasFile('logo'))) {
            if ($currentLogo && !str_starts_with($currentLogo, 'http') && Storage::disk('public')->exists($currentLogo)) {
                Storage::disk('public')->delete($currentLogo);
            }
            AppSetting::set('app_logo', '', 'branding');
        } elseif ($request->hasFile('logo')) {
            // Delete old file if present
            if ($currentLogo && !str_starts_with($currentLogo, 'http') && Storage::disk('public')->exists($currentLogo)) {
                Storage::disk('public')->delete($currentLogo);
            }
            $logoPath = $request->file('logo')->store('branding', 'public');
            AppSetting::set('app_logo', $logoPath, 'branding');
        }

        // 3. Favicon Handling
        $removeFavicon = filter_var($request->input('remove_favicon'), FILTER_VALIDATE_BOOLEAN);
        $currentFavicon = AppSetting::get('app_favicon');

        if ($removeFavicon && empty($request->hasFile('favicon'))) {
            if ($currentFavicon && !str_starts_with($currentFavicon, 'http') && Storage::disk('public')->exists($currentFavicon)) {
                Storage::disk('public')->delete($currentFavicon);
            }
            AppSetting::set('app_favicon', '', 'branding');
        } elseif ($request->hasFile('favicon')) {
            if ($currentFavicon && !str_starts_with($currentFavicon, 'http') && Storage::disk('public')->exists($currentFavicon)) {
                Storage::disk('public')->delete($currentFavicon);
            }
            $faviconPath = $request->file('favicon')->store('branding', 'public');
            AppSetting::set('app_favicon', $faviconPath, 'branding');
        }

        $newValues = [
            'app_name' => $appName,
            'app_tagline' => $appTagline,
            'app_logo' => AppSetting::get('app_logo'),
            'app_favicon' => AppSetting::get('app_favicon'),
            'company_address' => $companyAddress,
            'company_phone' => $companyPhone,
            'company_email' => $companyEmail,
            'receipt_footer' => $receiptFooter,
        ];

        // Audit Log
        if (class_exists(AuditLog::class)) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'update_branding_settings',
                'auditable_type' => AppSetting::class,
                'auditable_id' => 0,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return redirect()->route('admin.branding.index')->with('success', 'Site branding and identity settings updated successfully.');
    }
}
