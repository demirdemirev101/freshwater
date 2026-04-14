<?php

namespace App\Jobs;

use App\Mail\AdminShipmentFailedMail;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyAdminShipmentFailedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shipmentId
    ) {}

    /**
     * Sends a notification email to the administrator when a shipment fails to be created or sent to Econt. 
     * It retrieves the shipment details using the provided shipment ID and sends an email using the AdminShipmentFailedMail mailable class,
     *  which includes the shipment information and error details. 
     * This job is dispatched from the SendShipmentToEcont listener when a shipment fails to be sent to Econt.
     */
    public function handle(): void
    {
        $shipment = Shipment::with('order')->find($this->shipmentId);

        if (!$shipment) {
            return;
        }

        Mail::to(config('mail.admin_address', 'admin@freshwater.bg'))
            ->send(new AdminShipmentFailedMail($shipment));
    }
}
