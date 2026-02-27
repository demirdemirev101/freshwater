<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;

class ConfirmBankTransferPolicy
{
    /**
     * Determines if the "Confirm Bank Transfer" action should be visible. This action is only relevant for orders that are paid via bank transfer
     *  and are not yet marked as paid and are in a status that indicates they are still being processed (pending, pending review, or processing).
     */
    public function canConfirmBankTransfer(Order $order): bool
    {
        return $order->payment_method === 'bank_transfer'
            && $order->payment_status !== PaymentStatus::PAID->value
            && in_array($order->status, [
                OrderStatus::PENDING->value,
                OrderStatus::PENDING_REVIEW->value,
                OrderStatus::PROCESSING->value,
            ], true);
    }
}
