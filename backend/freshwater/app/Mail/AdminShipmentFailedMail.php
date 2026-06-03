<?php

namespace App\Mail;

use App\Models\Shipment;
use App\Services\BackendUrlService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminShipmentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shipment $shipment) {}

    public function build()
    {
        $adminOrderUrl = app(BackendUrlService::class)->adminOrderEditUrl($this->shipment->order_id);

        return $this
            ->subject('Неуспешно създаване на пратка за поръчка #' . $this->shipment->order_id)
            ->view('emails.shipment.failed', [
                'shipment' => $this->shipment,
                'adminOrderUrl' => $adminOrderUrl,
            ]);
    }
}
