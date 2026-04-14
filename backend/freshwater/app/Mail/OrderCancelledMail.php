<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(public int $orderId)
    {
        $this->order = Order::with('items')->findOrFail($orderId);
    }

    public function build()
    {
        return $this
            ->subject('Отказана поръчка #' . $this->order->id)
            ->view('emails.order-cancelled')
            ->with([
                'order' => $this->order,
            ]);
    }
}
