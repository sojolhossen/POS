<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'store_id',
        'is_active',
        'current_session_id',
        'locale',
        'theme',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getRoleNameAttribute(): string
    {
        if ($this->role instanceof UserRole) {
            return ucwords(str_replace('_', ' ', $this->role->value));
        }
        return ucwords(str_replace('_', ' ', (string) ($this->role ?? 'User')));
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isStoreManager(): bool
    {
        return $this->role === UserRole::STORE_MANAGER;
    }

    public function isCashier(): bool
    {
        return $this->role === UserRole::CASHIER;
    }

    public function isWarehouseStaff(): bool
    {
        return $this->role === UserRole::WAREHOUSE_STAFF;
    }

    public function isAccountant(): bool
    {
        return $this->role === UserRole::ACCOUNTANT;
    }

    /**
     * Check if user has permission to access a functional module.
     * Super Admin bypasses all checks.
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roleVal = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
        return RolePermission::isAllowed($roleVal, $module);
    }

    /**
     * Store scoping check:
     * Super Admin and Warehouse Staff can view all / central warehouse.
     * Store Managers and Cashiers are strictly scoped to their assigned store.
     */
    public function canAccessStore(?int $storeId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$storeId) {
            return false;
        }

        if ($this->isWarehouseStaff()) {
            $store = Store::find($storeId);
            return $store && $store->is_warehouse;
        }

        return (int) $this->store_id === (int) $storeId;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
