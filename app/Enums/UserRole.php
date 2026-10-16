<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case STORE_MANAGER = 'store_manager';
    case CASHIER = 'cashier';
    case WAREHOUSE_STAFF = 'warehouse_staff';
    case ACCOUNTANT = 'accountant';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => __('messages.role_super_admin'),
            self::STORE_MANAGER => __('messages.role_store_manager'),
            self::CASHIER => __('messages.role_cashier'),
            self::WAREHOUSE_STAFF => __('messages.role_warehouse_staff'),
            self::ACCOUNTANT => __('messages.role_accountant'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
