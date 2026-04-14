<?php

namespace App\Jobs;

use App\Mail\ShipmentTrackingMail;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTrackingEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shipmentId
    ) {}

    /**
     * Sends a tracking email to the customer with the shipment details. It retrieves the shipment and associated order information, 
     * checks if the customer's email is available and then sends an email using the ShipmentTrackingMail mailable class.
     *  This job is dispatched after a shipment is confirmed by Econt, ensuring that customers receive timely updates about their shipments.
     * If the shipment or customer email is not found, the job simply returns without attempting to send an email, preventing unnecessary errors.
     *  This method ensures that customers are kept informed about their shipments, 
     */
    public function handle(): void
    {
        $shipment = Shipment::with('order')->find($this->shipmentId);

        if (!$shipment || !$shipment->order?->customer_email) {
            return;
        }

        Mail::to($shipment->order->customer_email)
            ->send(new ShipmentTrackingMail($shipment->id));
    }
}
