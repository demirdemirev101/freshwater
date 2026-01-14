<?php

namespace App\Services;

use App\Models\Order;
use App\Events\OrderReadyForShipment; // ✅ ВАЖНО
use Exception;

class PaymentService
{
    public function handle(Order $order): void
    {
        match ($order->payment_method) {
            'cod' => $this->handleCashOnDelivery($order),
            default => throw new Exception('Неразпознат метод на плащане.'),
        };
    }

    private function handleCashOnDelivery(Order $order): void
    {
        // COD: не е платено, но може да се изпрати
        $order->updateQuietly([
            'payment_status' => 'unpaid',
            'status'         => 'ready_for_shipment',
        ]);

        // 🔥 Event hook за Econt / shipment
        event(new OrderReadyForShipment($order));
    }
}