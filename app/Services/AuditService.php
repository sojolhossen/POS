<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'secret',
        'api_key',
    ];

    /**
     * Record an audit log entry.
     */
    public static function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?User $actor = null
    ): AuditLog {
        $user = $actor ?? Auth::user();

        $cleanedOld = $oldValues ? self::sanitize($oldValues) : null;
        $cleanedNew = $newValues ? self::sanitize($newValues) : null;

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $cleanedOld,
            'new_values' => $cleanedNew,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => substr(Request::userAgent() ?? 'System', 0, 500),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /**
     * Scrub sensitive keys from arrays.
     */
    protected static function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), self::$sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
