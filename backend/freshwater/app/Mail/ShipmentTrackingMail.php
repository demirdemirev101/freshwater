<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentTrackingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $shipmentId) {}

    public function build()
    {
        $shipment = Shipment::with('order')->find($this->shipmentId);
        $isReturn = $shipment?->direction === 'return';

        return $this
            ->subject($isReturn ? 'Заявката за връщане е създадена' : 'Вашата поръчка е изпратена')
            ->view('emails.shipment.tracking', [
                'shipment' => $shipment,
                'trackingNumber' => $shipment?->tracking_number,
                'labelUrl' => $shipment?->label_url,
                'mailTitle' => $isReturn ? 'Обратната пратка е създадена' : 'Пратката е изпратена',
                'mailSubtitle' => $isReturn
                    ? 'Създадохме обратната пратка в Еконт и вече може да бъде проследена.'
                    : 'Пратката ви е създадена в Еконт и вече може да бъде проследена.',
                'buttonLabel' => $isReturn ? 'Проследи обратната пратка' : 'Проследи пратката',
            ]);
    }
}
