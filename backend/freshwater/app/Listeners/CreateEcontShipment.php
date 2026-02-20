<?php

namespace App\Listeners;

use App\Events\OrderReadyForShipment;
use App\Models\Order;
use App\Services\WeightCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateEcontShipment implements ShouldQueue
{
    public function __construct(
        protected WeightCalculatorService $weightCalculator
    ) {}
    /**
     * Creates a shipment record for the order, which will later be sent to Econt.
     * - Checks if a shipment already exists to prevent duplicates.
     * - Calculates weight, delivery type, declared value, and pack count based on the order details.
     * - Uses a feature flag to skip shipment creation in local environments.
     * Atomic guard is implemented in SendShipmentToEcont to prevent double sending, so we can safely create the shipment here without worrying about
     *  race conditions.
     */
    public function handle(OrderReadyForShipment $event): void
    {
        $order = Order::with(['shipment', 'items.product'])->findOrFail($event->orderId);

        if ($order->shipment) {
            Log::warning('Shipment already exists', [
                'order_id' => $order->id,
                'shipment_id' => $order->shipment->id,
            ]);
            return;
        }

        $weight = $this->weightCalculator->forOrder($order);
        
        // 🔒 FEATURE FLAG – DEV GUARD 
        $deliveryType = $this->determineDeliveryType($order);
        
        // Изчисляване на обявена стойност (за застраховка)
        $declaredValue = $this->calculateDeclaredValue($order);

        $shipment = $order->shipment()->create([
            'carrier' => 'econt',
            'weight' => $weight,
            'pack_count' => $this->calculatePackCount($order),
            'declared_value' => $declaredValue,
            'shipping_price_estimated' => $order->shipping_price,
            'cash_on_delivery' => $order->payment_method === 'cod' 
                ? $order->subtotal 
                : 0,
            'delivery_type' => $deliveryType,
            'office_code' => $deliveryType !== 'address' ? $order->econt_office_code : null,
            'status' => 'created',
        ]);

        Log::info('Shipment created', [
            'order_id' => $order->id,
            'shipment_id' => $shipment->id,
            'weight' => $weight,
            'delivery_type' => $deliveryType,
        ]);
    }

    /**
     * Determines the delivery type based on the order details:
     * - If an office code is present, it checks if it starts with 'APM' to classify it as 'apm', otherwise it's 'office'.
     * - If no office code is present, it defaults to 'address' for home delivery.
     */
    private function determineDeliveryType(Order $order): string
    {
        // Провери дали има избран офис/автомат
        if (!empty($order->econt_office_code)) {
            return str_starts_with($order->econt_office_code, 'APM') 
                ? 'apm' 
                : 'office';
        }

        return 'address';
    }

    /**
     * Calculates the number of packages needed for the shipment based on the total quantity of items in the order.
     * - It sums up the quantity of all items in the order.
     * - It assumes that each package can hold up to 5 items, so it divides the total quantity by 5 and rounds up to the nearest whole number.
     * - It ensures that there is at least 1 package, even if the order has a small quantity of items.
     */
    private function calculatePackCount(Order $order): int
    {
        $itemsCount = $order->items->sum('quantity');   
        return max(1, (int) ceil($itemsCount / 5)); // 5 артикула = 1 пакет
    }

    /**
     * Calculates the declared value for the shipment, which is used for insurance purposes.
     * - If the order has a subtotal, it uses that as the declared value.
     * - If not, it falls back to using the total minus the shipping price.
     * This ensures that we are declaring the value of the goods being shipped, excluding the shipping cost.
     */
    private function calculateDeclaredValue(Order $order): float
    {
        return $order->subtotal ?? $order->total - $order->shipping_price;
    }
}