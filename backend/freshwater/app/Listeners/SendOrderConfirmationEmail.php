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
     * Sending an order confirmation email to the customer after they have placed an order.
     * This listener listens for the OrderPlaced event and checks if the payment method is not 'bank_transfer' or 'cod' (cash on delivery).
     *  If the payment method is eligible for sending a confirmation email, it updates the order to mark that the order confirmation email has been sent
     *  and then sends an email to the customer's email address with the order details using the OrderConfirmationMail mailable class.
     * @param OrderPlaced $event The event instance containing information about the placed order, which can be used to retrieve order details and send the confirmation email.
     * @return void
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
