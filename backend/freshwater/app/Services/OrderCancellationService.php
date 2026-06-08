<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Mail\OrderCancelledMail;
use App\Models\Order;
use App\Models\Shipment;
use App\Policies\CancelOrderPolicy;
use App\Services\Econt\EcontService;
use App\Services\Shipment\ShipmentCancellationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class OrderCancellationService
{
    public function __construct(
        private CancelOrderPolicy $cancelOrderPolicy,
        private EcontService $econtService,
        private OrderService $orderService,
        private ShipmentCancellationService $shipmentCancellationService,
        private StripeRefundService $stripeRefundService,
    ) {}

    public function cancel(Order $order): void
    {
        $order->loadMissing(['shipment', 'items.product']);

        if (! $this->cancelOrderPolicy->canCancelOrder($order)) {
            throw new InvalidArgumentException('Поръчката вече не може да бъде отказана.');
        }

        $shipment = $order->shipment;

        $this->assertStripeRefundCanBeAttempted($order);

        $labelWasCancelled = false;

        try {
            $labelWasCancelled = $this->deleteCarrierLabelIfNeeded($order, $shipment);
            $this->refundStripePaymentIfNeeded($order);
        } catch (\Throwable $e) {
            if ($labelWasCancelled && $shipment) {
                $this->shipmentCancellationService->clearCancelledShipmentData($shipment, [
                    'error_message' => 'Товарителницата беше анулирана, но отказът на поръчката не завърши: '.$e->getMessage(),
                ]);
            }

            throw $e;
        }

        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOrder->load(['shipment', 'items.product']);

            $this->orderService->releaseReservedStockForLockedOrder($lockedOrder);

            if ($lockedOrder->shipment) {
                $this->shipmentCancellationService->clearCancelledShipmentData($lockedOrder->shipment);
            }

            $lockedOrder->updateQuietly([
                'status' => 'cancelled',
            ]);
        });

        if ($order->customer_email) {
            Mail::to($order->customer_email)->send(new OrderCancelledMail($order->id));
        }
    }

    private function deleteCarrierLabelIfNeeded(Order $order, ?Shipment $shipment): bool
    {
        if (! $shipment || empty($shipment->carrier_shipment_id)) {
            return false;
        }

        if (! config('services.econt.enabled')) {
            Log::info('Econt label cancellation skipped because Econt is disabled', [
                'order_id' => $order->id,
                'shipment_id' => $shipment->id,
                'carrier_shipment_id' => $shipment->carrier_shipment_id,
            ]);

            return true;
        }

        $deleteResponse = $this->econtService->deleteLabels([$shipment->carrier_shipment_id]);

        Log::info('Econt delete label success', [
            'order_id' => $order->id,
            'shipment_id' => $shipment->id,
            'carrier_shipment_id' => $shipment->carrier_shipment_id,
            'response' => $deleteResponse,
        ]);

        return true;
    }

    private function assertStripeRefundCanBeAttempted(Order $order): void
    {
        if ($order->payment_method !== 'stripe') {
            return;
        }

        if (! in_array($order->payment_status, [
            PaymentStatus::PAID->value,
            PaymentStatus::PARTIALLY_REFUNDED->value,
        ], true)) {
            return;
        }

        $remaining = round(max(0, (float) $order->total - (float) $order->refunded_amount), 2);

        if ($remaining <= 0) {
            return;
        }

        if (blank($order->stripe_payment_intent_id)) {
            throw new InvalidArgumentException('Липсва Stripe payment intent за тази поръчка.');
        }
    }

    private function refundStripePaymentIfNeeded(Order $order): void
    {
        if ($order->payment_method !== 'stripe') {
            return;
        }

        if (! in_array($order->payment_status, [
            PaymentStatus::PAID->value,
            PaymentStatus::PARTIALLY_REFUNDED->value,
        ], true)) {
            return;
        }

        $remaining = round(max(0, (float) $order->total - (float) $order->refunded_amount), 2);

        if ($remaining <= 0) {
            return;
        }

        $this->stripeRefundService->refund($order, $remaining);
    }
}
