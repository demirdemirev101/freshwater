<?php

namespace App\Services\Shipment;

use App\Models\Shipment;

class ShipmentCancellationService
{
    /**
     * Clears the shipment data for a cancelled order. This is called when an order is cancelled and has an associated shipment with a carrier shipment ID.
     *  It attempts to cancel the label in Econt, and if successful (or if Econt is disabled), it clears the shipment data locally to prevent further processing
     *  or polling of the shipment.
     */
    public function clearCancelledShipmentData(Shipment $shipment, array $extra = []): void
    {
        $shipment->update(array_merge([
            'status' => 'cancelled',
            'label_url' => null,
            'carrier_payload' => null,
            'carrier_response' => null,
            'tracking_number' => null,
            'carrier_shipment_id' => null,
        ], $extra));
    }
}
