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
    protected $listen=[
        Login::class => [
            MergeGuestCart::class,
            ],

        OrderPlaced::class => [
            SendOrderConfirmationEmail::class,
            SendAdminOrderNotification::class,
            ClearCartAfterOrder::class,
        ],

        OrderReadyForShipment::class => [
            CreateEcontShipment::class,
            SendShipmentToEcont::class,
        ],
    ];
}
