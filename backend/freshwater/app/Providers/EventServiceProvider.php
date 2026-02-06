<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderReadyForShipment;
use App\Listeners\ClearCartAfterOrder;
use App\Listeners\CreateEcontShipment;
use App\Listeners\CreateShipment;
use App\Listeners\MergeGuestCart;
use App\Listeners\SendAdminOrderNotification;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\SendShipmentToEcont;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for merging guest carts with user carts.
     */
    protected $listen=[
        Login::class => [
            MergeGuestCart::class,
            ],
    /**
     * The event to listener mappings for clearing the cart, 
     *  sending notifications for the admin and the client,
     *  after an order is placed.
     */
        OrderPlaced::class => [
            SendOrderConfirmationEmail::class,
            SendAdminOrderNotification::class,
            ClearCartAfterOrder::class,
        ],
    /**
     * The event to listener mappings for creating and sending shipments
     *  to Econt when an order is ready for shipment.
     */
        OrderReadyForShipment::class => [
            CreateEcontShipment::class,
            SendShipmentToEcont::class,
        ],
    ];
}
