<?php

namespace App\Services\Shipment;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Econt\EcontService;
use Illuminate\Support\Facades\Log;
use App\Policies\ShipmentPollingPolicy;

class ShipmentTrackingSyncService
{
    private EcontService $econtService;
    private ShipmentPollingPolicy $shipmentPollingPolicy;
    
    public function __construct(EcontService $econtService, ShipmentPollingPolicy $shipmentPollingPolicy) {
        $this->econtService = $econtService;
        $this->shipmentPollingPolicy = $shipmentPollingPolicy;
    }
    
    /**
     * Polls the shipment status from Econt and updates the local shipment and order records accordingly. This method checks if shipment polling should
     *  continue, retrieves the latest shipment status from Econt, and updates the shipment status and order status based on the tracking information.
     *  If the shipment is delivered, it marks the order as completed. If the shipment is in transit, it marks the order as shipped.
     *  It also handles logging and notifications in case of errors or status changes.
     */
    public function syncShipmentTracking(Order $order): bool
    {
        $order = $order->fresh(['shipment']);
        
        if (! $order) {
            return false;
        }
        
        if (! $this->shipmentPollingPolicy->shouldPollShipmentStatus($order)) {
            return false;
        }

        $shipment = $order->shipment;

        if (! $shipment) {
            return  false;
        }

        if (empty($shipment->carrier_shipment_id)) {
            return false;
        }

        if (! config('services.econt.enabled')) {
            return false;
        }

        try {
            $response = $this->econtService->trackShipment($shipment->carrier_shipment_id);
            $result = $response['shipmentStatuses'][0] ?? null;

            if (! is_array($result)) {
                return  false; 
            }

            if (! empty($result['error'])) {
                Log::warning('Econt tracking error', [
                    'order_id' => $order->id,
                    'shipment_id' => $shipment->id,
                    'error' => $result['error'],
                ]);
                return false;
            }

            $status = $result['status'] ?? null;

            if (! is_array($status)) {
                return false;
            }

            $carrierResponse = $shipment->carrier_response;
            $carrierResponse = is_array($carrierResponse) ? $carrierResponse : [];
            $carrierResponse['tracking'] = $response;

            $updates = [
                'carrier_response' => $carrierResponse,
            ];

            $shortStatus = $status['shortDeliveryStatus'] ?? $status['shortDeliveryStatusEn'] ?? null;
            $shortStatusEn = $status['shortDeliveryStatusEn'] ?? null;
            $trackingEvents = $status['trackingEvents'] ?? [];

            $delivered = ! empty($status['deliveryTime'])
                || in_array($shortStatus, ['Доставена', 'Delivered'], true);

            $cancelledByCarrier = in_array($shortStatusEn, [
                'Cancelled before sending',
                'Cancelled after sending',
            ], true);

            $returningToSender = $shortStatusEn === 'Is returning to sender';

            $returnedToSender = $shortStatusEn === 'Returned to sender';

            $inTransit = ! $delivered && (
                ! empty($trackingEvents)
                || in_array($shortStatus, [
                    'Приета в Еконт',
                    'Пътува по линия',
                    'В куриер',
                    'В офис',
                    'В офис на приемащ куриер',
                    'Приета в офис в офис на предаващ куриер',
                    'Пристигнала в офис',
                    'Постъпила за обработка в Логистичен център',
                    'Prepared in eEcont',
                    'Accepted in Econt',
                    'In route',
                    'In courier',
                    'In pick up courier',
                    'Accepted in office',
                    'In delivery courier\'s office',
                    'Arrived in office',
                    'Arrival departure from hub',
                ], true)
            );

            if ($returnedToSender) {
                $updates['status'] = 'returned';
            } elseif ($returningToSender) {
                $updates['status'] = 'returning';
            } elseif ($cancelledByCarrier) {
                $updates['status'] = 'cancelled';
            } elseif ($delivered) {
                $updates['status'] = 'delivered';
            } elseif ($inTransit && $shipment->status !== 'delivered') {
                $updates['status'] = 'in_transit';
            }

            $shipment->fill($updates);
            $shipmentChanged = $shipment->isDirty();

            if ($shipmentChanged) {
                $shipment->save();
            }

            $orderChanged = false;

            if ($returnedToSender && $order->status !== OrderStatus::RETURNED->value) {
                $order->update(['status' => OrderStatus::RETURNED->value]);
                $orderChanged = true;
            } elseif ($returningToSender && $order->status !== OrderStatus::RETURN_REQUESTED->value) {
                $order->update(['status' => OrderStatus::RETURN_REQUESTED->value]);
                $orderChanged = true;
            } elseif ($cancelledByCarrier && $order->status !== OrderStatus::CANCELLED->value) {
                $order->update(['status' => OrderStatus::CANCELLED->value]);
                $orderChanged = true;
            } elseif ($delivered && $order->status !== OrderStatus::COMPLETED->value) {
                $order->update(['status' => OrderStatus::COMPLETED->value]);
                $orderChanged = true;
            } elseif (
                $inTransit
                && $order->status !== OrderStatus::SHIPPED->value
                && $order->status !== OrderStatus::COMPLETED->value
            ) {
                $order->update(['status' => OrderStatus::SHIPPED->value]);
                $orderChanged = true;
            }

            return $shipmentChanged || $orderChanged;
            
        } catch (\Throwable $e) {
            Log::error('Econt tracking failed', [
                'order_id' => $order->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
