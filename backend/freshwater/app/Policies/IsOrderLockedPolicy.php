<?php

namespace App\Policies;

use App\Models\Order;

class IsOrderLockedPolicy
{
    /**
     * Determines if the current order is locked. An order is considered locked if it is not in a status that allows editing
     *  (pending review or bank transfer that is not paid).
     */
    public function isLocked(Order $order): bool
    {
        return ! ($order->status === 'pending_review'
            || ($order->payment_method === 'bank_transfer' && $order->payment_status !== 'paid'));
    }
}
