<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\RolePermission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    /**
     * Display the Role & Permissions matrix.
     * Accessible strictly by Super Admin (Shop Owner).
     */
    public function index()
    {
        $allRoles = UserRole::cases();
        $modules = RolePermission::defaultPermissions();

        // Categorize modules for clean UI grouping
        $categories = [];
        foreach ($modules as $key => $info) {
            $cat = $info['category'] ?? 'General';
            $categories[$cat][$key] = $info;
        }

        // Build active permission matrix
        $matrix = [];
        $savedPermissions = RolePermission::all()->keyBy(function ($item) {
            return $item->role . '_' . $item->module;
        });

        foreach ($allRoles as $role) {
            $roleVal = $role->value;
            $matrix[$roleVal] = [];
            foreach ($modules as $modKey => $info) {
                if ($roleVal === 'super_admin') {
                    // Super Admin (Shop Owner) always has full access
                    $matrix[$roleVal][$modKey] = true;
                } else {
                    $key = $roleVal . '_' . $modKey;
                    if (isset($savedPermissions[$key])) {
                        $matrix[$roleVal][$modKey] = (bool) $savedPermissions[$key]->is_allowed;
                    } else {
                        // Fall back to defaults
                        $matrix[$roleVal][$modKey] = in_array($roleVal, $info['defaults'], true);
                    }
                }
            }
        }

        return view('roles.permissions', compact('allRoles', 'modules', 'categories', 'matrix'));
    }

    /**
     * Save updated permissions from matrix.
     */
    public function update(Request $request)
    {
        $inputPermissions = $request->input('permissions', []);
        $allRoles = UserRole::cases();
        $modules = RolePermission::defaultPermissions();

        $oldValues = RolePermission::all()->toArray();

        foreach ($allRoles as $role) {
            $roleVal = $role->value;
            // Super Admin permissions are permanent and cannot be locked
            if ($roleVal === 'super_admin') {
                continue;
            }

            foreach ($modules as $modKey => $info) {
                $isAllowed = isset($inputPermissions[$roleVal][$modKey]) && (string) $inputPermissions[$roleVal][$modKey] === '1';

                RolePermission::updateOrCreate(
                    [
                        'role' => $roleVal,
                        'module' => $modKey,
                    ],
                    [
                        'is_allowed' => $isAllowed,
                    ]
                );
            }
        }

        // Tamper-evident Audit Trail
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'ROLE_PERMISSIONS_UPDATED',
            'auditable_type' => RolePermission::class,
            'auditable_id' => null,
            'old_values' => ['count' => count($oldValues)],
            'new_values' => ['updated_by' => auth()->user()->name, 'timestamp' => now()->toIso8601String()],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => 'Store Owner updated role-based access control and sidebar lock permissions.',
            'created_at' => now(),
        ]);

        return redirect()->route('roles.permissions.index')
            ->with('success', 'Role access permissions updated successfully! Locked modules will now show lock badges in the navigation.');
    }

    /**
     * Reset all role permissions to factory defaults.
     */
    public function resetDefaults(Request $request)
    {
        RolePermission::truncate();

        // Audit Trail
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'ROLE_PERMISSIONS_RESET_DEFAULTS',
            'auditable_type' => RolePermission::class,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => ['action' => 'reset_to_default_matrix'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => 'Store Owner reset role permissions to factory defaults.',
            'created_at' => now(),
        ]);

        return redirect()->route('roles.permissions.index')
            ->with('success', 'All role permissions have been reset to factory defaults.');
    }
}
