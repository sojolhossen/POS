<?php

namespace App\Enums;

enum StockAdjustmentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('messages.status_pending'),
            self::APPROVED => __('messages.status_approved'),
            self::REJECTED => __('messages.status_rejected'),
        };
    }
}
