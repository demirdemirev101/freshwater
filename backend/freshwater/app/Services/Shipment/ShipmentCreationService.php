<?php

namespace App\Services\Shipment;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ShipmentCreationService
{
    public function __construct(
        private ShipmentMeasurementService $shipmentMeasurementService
    ) {}

    public function createForOrder(int $orderId): void
    {
        $order = Order::with(['shipment', 'items.product'])->findOrFail($orderId);

        if ($order->shipment) {
            Log::warning('Shipment already exists', [
                'order_id' => $order->id,
                'shipment_id' => $order->shipment->id,
            ]);

            return;
        }

        $deliveryType = $this->determineDeliveryType($order);
        $declaredValue = $this->calculateDeclaredValue($order);
        $shipment = $this->shipmentMeasurementService->applyToShipment($order->shipment()->make([
            'carrier' => 'econt',
            'pack_count' => $this->calculatePackCount($order),
            'declared_value' => $declaredValue,
            'shipping_price_estimated' => $order->shipping_price,
            'cash_on_delivery' => $order->payment_method === 'cod'
                ? $order->subtotal
                : 0,
            'delivery_type' => $deliveryType,
            'office_code' => $deliveryType !== 'address' ? $order->econt_office_code : null,
            'status' => 'created',
        ]), $order);

        $shipment->save();

        Log::info('Shipment created', [
            'order_id' => $order->id,
            'shipment_id' => $shipment->id,
            'weight' => $shipment->weight,
            'shipment_type' => $shipment->shipment_type,
            'delivery_type' => $deliveryType,
        ]);
    }

    private function determineDeliveryType(Order $order): string
    {
        if (! empty($order->econt_office_code)) {
            return str_starts_with($order->econt_office_code, 'APM')
                ? 'apm'
                : 'office';
        }

        return 'address';
    }

    private function calculatePackCount(Order $order): int
    {
        $itemsCount = $order->items->sum('quantity');

        return max(1, (int) ceil($itemsCount / 5));
    }

    private function calculateDeclaredValue(Order $order): float
    {
        return $order->subtotal ?? $order->total - $order->shipping_price;
    }
}
