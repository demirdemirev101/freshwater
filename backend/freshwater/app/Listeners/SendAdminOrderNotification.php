<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\AdminOrderNotificationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendAdminOrderNotification implements ShouldQueue
{
    /**
        * This listener listens for the OrderPlaced event and sends an email notification to the admin with the details of the newly placed order. 
        * It first retrieves the order details using the order ID from the event, then checks if an admin notification has already been sent for this order to avoid duplicate notifications. 
        * If not, it updates the order to mark that the admin notification has been sent and proceeds to send an email to the admin's email address with the order information.
        * @param OrderPlaced $event The event instance containing information about the placed order, which can be used to retrieve order details and send the notification.
        * @return void
     */
    public function handle(OrderPlaced $event): void
    {
        $order = Order::with('items')->findOrFail($event->orderId);

        $updated = Order::where('id', $order->id)
            ->whereNull('admin_notification_sent_at')
            ->update([
                'admin_notification_sent_at' => now(),
            ]);

        if (! $updated) {
            return;
        }

        Mail::to('admin@freshwater.bg')
            ->send(new AdminOrderNotificationMail($order->id));
    }
}
