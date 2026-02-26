<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;

class CancelOrderPolicy
{
    private const RETURN_REQUESTABLE_STATUSES = [
        OrderStatus::SHIPPED->value,
        OrderStatus::IN_TRANSIT->value,
        OrderStatus::COMPLETED->value,
    ];

    private const CANCELLABLE_SHIPMENT_STATUSES = [
        'created',
        'pending',
        'confirmed',
    ];

    public function canCancelOrder(Order $order): bool
    {
        if (in_array($order->status, [
            OrderStatus::CANCELLED->value,
            OrderStatus::RETURN_REQUESTED->value,
            OrderStatus::RETURNED->value,
            OrderStatus::COMPLETED->value,
        ], true)) {
            return false;
        }

        $order->loadMissing('shipment');

        $shipmentStatus = $order->shipment?->status;

        if ($shipmentStatus === null) {
            return true;
        }

        return in_array($shipmentStatus, self::CANCELLABLE_SHIPMENT_STATUSES, true);
    }

    public function canRequestReturn(Order $order): bool
    {
        return in_array($order->status, self::RETURN_REQUESTABLE_STATUSES, true);
    }
}

