<?php

namespace App\Providers;

use App\Listeners\MergeGuestCart;
use App\Listeners\SendOrderConfirmationEmail;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen=[
        Login::class => [
            MergeGuestCart::class,
            SendOrderConfirmationEmail::class,
        ]
    ];
}
