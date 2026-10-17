<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxSettingController extends Controller
{
    /**
     * Show Tax / VAT Configuration Page (Super Admin Only).
     */
    public function index()
    {
        $vatEnabled = AppSetting::isVatEnabled();
        $vatRate = (float) AppSetting::get('vat_rate', 5.00);
        $vatLabel = AppSetting::getVatLabel();

        return view('settings.tax', compact('vatEnabled', 'vatRate', 'vatLabel'));
    }

    /**
     * Update Tax / VAT Settings (Super Admin Only).
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'vat_enabled' => ['required'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'vat_label' => ['nullable', 'string', 'max:50'],
        ]);

        $vatEnabled = filter_var($validated['vat_enabled'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        $vatRate = number_format((float) $validated['vat_rate'], 2, '.', '');
        $vatLabel = !empty($validated['vat_label']) ? trim($validated['vat_label']) : 'Tax / VAT';

        $oldEnabled = AppSetting::get('vat_enabled', '1');
        $oldRate = AppSetting::get('vat_rate', '5.00');

        AppSetting::set('vat_enabled', $vatEnabled, 'tax');
        AppSetting::set('vat_rate', $vatRate, 'tax');
        AppSetting::set('vat_label', $vatLabel, 'tax');

        // Audit Log
        if (class_exists(AuditLog::class)) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'update_tax_settings',
                'auditable_type' => AppSetting::class,
                'auditable_id' => 0,
                'old_values' => ['vat_enabled' => $oldEnabled, 'vat_rate' => $oldRate],
                'new_values' => ['vat_enabled' => $vatEnabled, 'vat_rate' => $vatRate, 'vat_label' => $vatLabel],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $statusMsg = $vatEnabled === '1'
            ? "Tax / VAT updated: Enabled at {$vatRate}%"
            : "Tax / VAT has been turned OFF (0% VAT applied to sales)";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $statusMsg,
                'vat_enabled' => $vatEnabled === '1',
                'vat_rate' => (float) $vatRate,
                'vat_label' => $vatLabel,
            ]);
        }

        return redirect()->back()->with('success', $statusMsg);
    }
}
