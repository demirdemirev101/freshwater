<?php

namespace App\Listeners;

use App\Events\OrderReadyForShipment;
use App\Events\ShipmentCreated;
use App\Services\Shipment\ShipmentCreationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateEcontShipment implements ShouldQueue
{
    public function handle(OrderReadyForShipment $event): void
    {
        app(ShipmentCreationService::class)->createForOrder($event->orderId);

        event(new ShipmentCreated($event->orderId));
    }
}
