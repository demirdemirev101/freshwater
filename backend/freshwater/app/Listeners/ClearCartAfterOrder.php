<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\CartService;

class ClearCartAfterOrder
{
    /**
     * Clearing the cart after an order is placed to ensure that the user's cart is empty and ready for new items after they have completed a purchase.
     *  This listener listens for the OrderPlaced event and then calls the clear method on the CartService to empty the cart.
     * @param OrderPlaced $event The event instance containing information about the placed order,
     *  which can be used if needed to perform additional actions related to clearing the cart.
     * @return void
     */
    public function handle(OrderPlaced $event): void
    {
        app(CartService::class)->clear();
    }
}
