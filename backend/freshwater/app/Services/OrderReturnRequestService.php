<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Policies\CancelOrderPolicy;
use App\Services\Shipment\ShipmentReturnService;
use InvalidArgumentException;

class OrderReturnRequestService
{
    public function __construct(
        private CancelOrderPolicy $cancelOrderPolicy,
        private ShipmentReturnService $shipmentReturnService,
        private StripeRefundService $stripeRefundService,
    ) {}

    public function requestReturn(Order $order): Shipment
    {
        $order->loadMissing(['returnShipment', 'shipment', 'items.product']);

        if (! $this->cancelOrderPolicy->canRequestReturn($order)) {
            throw new InvalidArgumentException('За тази поръчка не може да се заяви връщане.');
        }

        $this->assertStripeRefundCanBeAttempted($order);

        $shipment = $this->shipmentReturnService->createReturnLabel($order);

        try {
            $this->refundStripePaymentIfNeeded($order->fresh());
        } catch (\Throwable $e) {
            $shipment->update([
                'status' => 'error',
                'error_message' => 'Обратната пратка беше създадена, но Stripe refund-ът не завърши: '.$e->getMessage(),
            ]);

            throw $e;
        }

        return $shipment->fresh();
    }

    private function assertStripeRefundCanBeAttempted(Order $order): void
    {
        if ($this->remainingStripeRefund($order) <= 0) {
            return;
        }

        if (blank($order->stripe_payment_intent_id)) {
            throw new InvalidArgumentException('Липсва Stripe payment intent за тази поръчка.');
        }
    }

    private function refundStripePaymentIfNeeded(Order $order): void
    {
        $remaining = $this->remainingStripeRefund($order);

        if ($remaining <= 0) {
            return;
        }

        $this->stripeRefundService->refund($order, $remaining, OrderStatus::RETURN_REQUESTED);
    }

    private function remainingStripeRefund(Order $order): float
    {
        if ($order->payment_method !== 'stripe') {
            return 0.0;
        }

        if (! in_array($order->payment_status, [
            PaymentStatus::PAID->value,
            PaymentStatus::PARTIALLY_REFUNDED->value,
        ], true)) {
            return 0.0;
        }

        return round(max(0, (float) $order->total - (float) $order->refunded_amount), 2);
    }
}
