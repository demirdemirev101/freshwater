<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;

class ShipmentPollingPolicy
{
    // Statuses that indicate that shipment polling should stop, as the shipment is either delivered or the order is cancelled/returned.
    private const SHIPMENT_POLL_STOP_STATUSES = [
        OrderStatus::COMPLETED->value,
        OrderStatus::CANCELLED->value,
        OrderStatus::RETURN_REQUESTED->value,
        OrderStatus::RETURNED->value,
    ];

    /**
     * Determines if shipment polling should stop based on the order status. If the order is completed, cancelled, or return requested/returned,
     *  there is no need to continue polling for shipment updates.
     */
    private function shouldStopShipmentPolling(Order $record): bool
    {
        return in_array($record->status, self::SHIPMENT_POLL_STOP_STATUSES, true);
    }
    
    /**
     * Determines if shipment polling should continue for the current order. Polling should continue
     *  if the order has a shipment with a carrier shipment ID and is not in a status that indicates it should stop.
     */
    public function shouldPollShipmentStatus(Order $record): bool
    {
        if (! $record) {
            return false;
        }

        if ($this->shouldStopShipmentPolling($record)) {
            return false;
        }

        return ! empty($record->shipment?->carrier_shipment_id);
    }

}
