<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\AdminOrderNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendAdminOrderNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        Mail::to('admin@freshwater.bg')
            ->send(new AdminOrderNotificationMail($event->order));
    }
}
