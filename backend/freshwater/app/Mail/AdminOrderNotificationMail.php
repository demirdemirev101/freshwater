<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\BackendUrlService;
use App\Services\OrderDeliveryDetailsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminOrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(public int $orderId)
    {
        $this->order = Order::with('items')->findOrFail($orderId);
    }

    public function build()
    {
        $deliveryDetails = app(OrderDeliveryDetailsService::class)->forEmail($this->order);
        $adminOrderUrl = app(BackendUrlService::class)->adminOrderEditUrl($this->order->id);

        return $this
            ->subject('Нова поръчка #' . $this->orderId)
            ->view('emails.admin-order-notification')
            ->with([
                'order' => $this->order,
                'deliveryDetails' => $deliveryDetails,
                'adminOrderUrl' => $adminOrderUrl,
            ]);
    }
}
