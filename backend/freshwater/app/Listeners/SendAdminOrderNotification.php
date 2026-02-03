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
     * Handle the event.
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
