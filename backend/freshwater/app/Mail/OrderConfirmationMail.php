<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(public int $orderId) 
    {
        $this->order=Order::with('items')->findOrFail($orderId);
    }

    public function build()
    {
        return $this
            ->subject('Потвърждение на поръчката #' .  $this->order->id)
            ->view('emails.order-confirmation')
            ->with([
                'order' => $this->order,
            ]);
    }
}
