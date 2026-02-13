<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\Econt\EcontPayloadMapper;
use App\Services\Econt\EcontService;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    public function __construct(
        private EcontService $econtService,
        private EcontPayloadMapper $econtPayloadMapper,
        private WeightCalculatorService $weightCalculator
    ) {}

    /**
     * Apply shipping cost to the order based on current settings and order details. The method checks various conditions such as delivery enablement,
     *  free delivery thresholds, payment methods and Econt service availability to determine the appropriate shipping price for the order.
     *  If any of the conditions indicate that shipping should be free, it sets the shipping price to zero. Otherwise,
     *  it calculates the shipping cost using the Econt service and applies it to the order.
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
     * Apply totals to the order by calculating the total price based on the subtotal and shipping price.
     */
    public function applyTotals(Order $order): void
    {
        // Recalculate shipping first
        $this->applyShipping($order);

        // Calculate total
        $order->total = ($order->subtotal ?? 0) + ($order->shipping_price ?? 0);
    }

    /**
     * Calculate the Econt shipping cost for the given order. This method builds a shipment object based on the order details,
     *  maps it to the Econt API payload format, and then calls the Econt service to calculate the shipping cost. If the calculation fails for any reason,
     *  it logs a warning and returns 0.0.
     */
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
    /**
     * Public method to calculate Econt shipping for an order,
     *  which can be used in contexts where only the shipping cost is needed without applying it to the order totals.
     *  It internally calls the private calculateEcontShipping method to perform the actual calculation and returns the shipping cost as a float.
     */
    public function calculateEcontShippingForOrder(Order $order): float
    {
        return $this->calculateEcontShipping($order);
    }
    /**
     * Build a Shipment object based on the given order details, which is used for Econt shipping cost calculation. 
     *  The method loads the necessary relationships, calculates the total weight, determines the pack count,
     *  sets the declared value and cash on delivery amount based on the order's subtotal and payment method
     *  and determines the delivery type and office code if applicable.
     *  The resulting Shipment object is then returned for use in the Econt API payload mapping.
     */
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
    /**
     * Determine the delivery type for the given order based on its details.
     *  The method checks if the order has an Econt office code and determines the delivery type accordingly.
     *  If the office code starts with 'APM', it returns 'apm',
     *  if it has an office code but doesn't start with 'APM', it returns 'office'. If there is no office code, it defaults to 'address' delivery type.
     */
    private function determineDeliveryType(Order $order): string
    {
        if (! empty($order->econt_office_code)) {
            return str_starts_with($order->econt_office_code, 'APM')
                ? 'apm'
                : 'office';
        }

        return 'address';
    }
    /**
     * Calculate the number of packs needed for the given order based on the total quantity of items. 
     *  The method sums up the quantity of all items in the order
     *  and divides it by 5 (assuming each pack can hold up to 5 items) to determine the number of packs required.
     *  It ensures that at least one pack is returned even if the total quantity is 0.
     */
    private function calculatePackCount(Order $order): int
    {
        $itemsCount = $order->items->sum('quantity');

        return max(1, (int) ceil($itemsCount / 5));
    }
}
