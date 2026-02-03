<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\CartService;

class ClearCartAfterOrder
{
    public function handle(OrderPlaced $event): void
    {
        app(CartService::class)->clear();
    }
}
