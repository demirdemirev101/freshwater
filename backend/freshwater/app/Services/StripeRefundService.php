<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Stripe\StripeClient;

class StripeRefundService
{
    public function refund(Order $order, float $amount, ?OrderStatus $fullyRefundedOrderStatus = null): object
    {
        if ($order->payment_method !== 'stripe') {
            throw new InvalidArgumentException('Това действие може да възстановява средства само за Stripe поръчки.');
        }

        if (! in_array($order->payment_status, [
            PaymentStatus::PAID->value,
            PaymentStatus::PARTIALLY_REFUNDED->value,
        ], true)) {
            throw new InvalidArgumentException('Само платени Stripe поръчки могат да бъдат възстановени.');
        }

        if (blank($order->stripe_payment_intent_id)) {
            throw new InvalidArgumentException('Липсва Stripe payment intent за тази поръчка.');
        }

        $remaining = max(0, (float) $order->total - (float) $order->refunded_amount);
        $amount = round($amount, 2);

        if ($amount <= 0 || $amount > $remaining) {
            throw new InvalidArgumentException('Сумата за възстановяване трябва да е по-голяма от нула и да не надвишава оставащата платена сума.');
        }

        $refund = $this->stripe()->refunds->create([
            'payment_intent' => $order->stripe_payment_intent_id,
            'amount' => $this->toMinorUnit($amount),
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $this->applyRefund($order, $refund->id ?? null, $amount, $fullyRefundedOrderStatus);

        return $refund;
    }

    public function applyRefund(
        Order $order,
        ?string $refundId,
        float $amount,
        ?OrderStatus $fullyRefundedOrderStatus = null
    ): void {
        DB::transaction(function () use ($order, $refundId, $amount, $fullyRefundedOrderStatus): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $refundedAmount = min(
                (float) $lockedOrder->total,
                round((float) $lockedOrder->refunded_amount + $amount, 2),
            );

            $isFullyRefunded = $refundedAmount >= (float) $lockedOrder->total;

            $lockedOrder->updateQuietly([
                'stripe_refund_id' => $refundId ?? $lockedOrder->stripe_refund_id,
                'refunded_amount' => $refundedAmount,
                'refunded_at' => now(),
                'payment_status' => $isFullyRefunded
                    ? PaymentStatus::REFUNDED->value
                    : PaymentStatus::PARTIALLY_REFUNDED->value,
                'status' => $isFullyRefunded
                    ? ($fullyRefundedOrderStatus?->value ?? OrderStatus::RETURNED->value)
                    : $lockedOrder->status,
            ]);
        });
    }

    private function stripe(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.sk'));
    }

    private function toMinorUnit(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
