<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = Order::with('items')->findOrFail($event->orderId);

        if ($order->payment_method === 'bank_transfer') {
            return;
        }

        if (!Cache::add("order_confirmation_sent_{$order->id}", true, now()->addMinutes(10))) {
            return;
        }

        Mail::to($order->customer_email)
            ->send(new OrderConfirmationMail($order->id));
    }
}
