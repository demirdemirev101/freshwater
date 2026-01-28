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
