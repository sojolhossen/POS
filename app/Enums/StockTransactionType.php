<?php

namespace App\Enums;

enum StockTransactionType: string
{
    case OPENING_BALANCE = 'opening_balance';
    case WAREHOUSE_RECEIVE = 'warehouse_receive';
    case ADJUSTMENT_INCREASE = 'adjustment_increase';
    case ADJUSTMENT_DECREASE = 'adjustment_decrease';
    case TRANSFER_DISPATCH = 'transfer_dispatch';
    case TRANSFER_RECEIVE = 'transfer_receive';
    case SALE = 'sale';
    case RETURN = 'return';
    case RETURN_SUPPLIER = 'return_supplier';
    case REVERSAL = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::OPENING_BALANCE => __('messages.tx_opening_balance'),
            self::WAREHOUSE_RECEIVE => __('messages.tx_warehouse_receive'),
            self::ADJUSTMENT_INCREASE => __('messages.tx_adjustment_increase'),
            self::ADJUSTMENT_DECREASE => __('messages.tx_adjustment_decrease'),
            self::TRANSFER_DISPATCH => __('messages.tx_transfer_dispatch'),
            self::TRANSFER_RECEIVE => __('messages.tx_transfer_receive'),
            self::SALE => __('messages.tx_sale'),
            self::RETURN => __('messages.tx_return'),
            self::RETURN_SUPPLIER => 'Supplier Return',
            self::REVERSAL => __('messages.tx_reversal'),
        };
    }
}
