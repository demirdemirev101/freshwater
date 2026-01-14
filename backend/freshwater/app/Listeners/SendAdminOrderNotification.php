<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\AdminOrderNotificationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminOrderNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = Order::with('items')->findOrFail($event->orderId);
        
        Mail::to('admin@freshwater.bg')
            ->send(new AdminOrderNotificationMail($order->id));
    }
}
