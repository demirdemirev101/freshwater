<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = Order::with('items')->findOrFail($event->orderId);

        if (in_array($order->payment_method, ['bank_transfer', 'cod'], true)) {
            return;
        }

        $updated = Order::where('id', $order->id)
            ->whereNull('order_confirmation_sent_at')
            ->update([
                'order_confirmation_sent_at' => now(),
            ]);

        if (! $updated) {
            return;
        }

        Mail::to($order->customer_email)
            ->send(new OrderConfirmationMail($order->id));
    }
}
