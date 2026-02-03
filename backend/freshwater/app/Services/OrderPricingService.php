<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\Econt\EcontPayloadMapper;
use App\Services\Econt\EcontService;
use Illuminate\Support\Facades\Log;

class OrderPricingService
{
    public function __construct(
        private EcontService $econtService,
        private EcontPayloadMapper $econtPayloadMapper,
        private WeightCalculatorService $weightCalculator
    ) {}

    /**
     * Apply shipping rules to the order.
     * Call this ONLY when creating or updating the order
     * or when ADMIN changes explicitly recalculates
     */
    public function applyShipping(Order $order): void
    {
        $settings = Setting::current();

        if (! $settings->delivery_enabled) {
            $order->shipping_price = 0;
            return;
        }

        if ($settings->free_delivery_over !== null &&
            $order->subtotal >= $settings->free_delivery_over
        ) {
            $order->shipping_price = 0;
            return;
        }

        if (in_array($order->payment_method, ['bank_transfer', 'cod'], true)) {
            // Defer Econt calculate to async job for bank transfer and COD.
            $order->shipping_price = 0;
            return;
        }

        if (! config('services.econt.enabled')) {
            $order->shipping_price = 0;
            return;
        }

        $order->shipping_price = $this->calculateEcontShipping($order);
    }

    /**
     * Apply full pricing logic.
     */
    public function applyTotals(Order $order): void
    {
        // Recalculate shipping first
        $this->applyShipping($order);

        // Calculate total
        $order->total = ($order->subtotal ?? 0) + ($order->shipping_price ?? 0);
    }

    private function calculateEcontShipping(Order $order): float
    {
        try {
            $shipment = $this->buildShipmentForPricing($order);
            $payload = $this->econtPayloadMapper->map($shipment);
            $response = $this->econtService->createLabel($payload, 'calculate');
            $price = $response['label']['totalPrice'] ?? null;

            return $price !== null ? (float) $price : 0.0;
        } catch (\Throwable $e) {
            Log::warning('Econt shipping calculate failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    public function calculateEcontShippingForOrder(Order $order): float
    {
        return $this->calculateEcontShipping($order);
    }

    private function buildShipmentForPricing(Order $order): Shipment
    {
        $order->loadMissing('items.product');

        $shipment = new Shipment();
        $shipment->setRelation('order', $order);
        $shipment->weight = $this->weightCalculator->forOrder($order);
        $shipment->pack_count = $this->calculatePackCount($order);
        $shipment->declared_value = (float) ($order->subtotal ?? 0);
        $shipment->cash_on_delivery = $order->payment_method === 'cod'
            ? (float) ($order->subtotal ?? 0)
            : 0.0;
        $shipment->delivery_type = $this->determineDeliveryType($order);
        $shipment->office_code = $shipment->delivery_type !== 'address'
            ? $order->econt_office_code
            : null;

        return $shipment;
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
}
