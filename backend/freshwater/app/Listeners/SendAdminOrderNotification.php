<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\AdminOrderNotificationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminOrderNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = Order::with('items')->findOrFail($event->orderId);
        
        if (!Cache::add("admin_order_notification_sent_{$order->id}", true, now()->addMinutes(10))) {
            return;
        }

        Mail::to('admin@freshwater.bg')
            ->send(new AdminOrderNotificationMail($order->id));
    }
}
