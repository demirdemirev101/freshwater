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
