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
        
        // Определяне на delivery type
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

        // Автоматично trigger на изпращане към Еконт
        // SendShipmentToEcont ще се изпълни от event-а
    }

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

    private function calculatePackCount(Order $order): int
    {
        // Прост вариант - 1 пакет
        // По-сложна логика според обем/брой продукти
        $itemsCount = $order->items->sum('quantity');
        
        return max(1, (int) ceil($itemsCount / 5)); // 5 артикула = 1 пакет
    }

    private function calculateDeclaredValue(Order $order): float
    {
        // Обявена стойност за застраховка
        // Обикновено е стойността на стоките (без доставка)
        return $order->subtotal ?? $order->total - $order->shipping_price;
    }
}