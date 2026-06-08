<?php

namespace App\Services;

use App\Models\Order;
use Stripe\StripeClient;

class StripeCheckoutService
{
    private const CURRENCY = 'eur';

    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient((string) config('services.stripe.sk'));
    }

    public function createSession(Order $order, ?string $sessionId = null): object
    {
        $order->loadMissing('items');

        $lineItems = $order->items->map(fn ($item) => [
            'price_data' => [
                'currency' => self::CURRENCY,
                'product_data' => [
                    'name' => $item->product_name,
                ],
                'unit_amount' => $this->toMinorUnit((float) $item->price),
            ],
            'quantity' => $item->quantity,
        ])->values()->all();

        if ((float) $order->shipping_price > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => self::CURRENCY,
                    'product_data' => [
                        'name' => 'Доставка',
                    ],
                    'unit_amount' => $this->toMinorUnit((float) $order->shipping_price),
                ],
                'quantity' => 1,
            ];
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');

        return $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'locale' => 'bg',
            'payment_method_types' => ['card'],
            'customer_email' => $order->customer_email,
            'line_items' => $lineItems,
            'success_url' => $frontendUrl.'/checkout/success?order_id='.$order->id,
            'cancel_url' => $frontendUrl.'/checkout/cancel?order_id='.$order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
                'cart_session_id' => (string) ($sessionId ?? ''),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'cart_session_id' => (string) ($sessionId ?? ''),
                ],
            ],
        ]);
    }

    private function toMinorUnit(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
