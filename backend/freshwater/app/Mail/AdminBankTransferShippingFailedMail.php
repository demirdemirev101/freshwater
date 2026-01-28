<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminBankTransferShippingFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $errorMessage
    ) {}

    public function build()
    {
        return $this
            ->subject('Грешка при изчисляване на доставка (банков превод)')
            ->view('emails.admin-bank-transfer-shipping-failed')
            ->with([
                'orderId' => $this->orderId,
                'errorMessage' => $this->errorMessage,
            ]);
    }
}
