<?php

namespace App\Listeners;

use App\Services\CartService;
use Illuminate\Auth\Events\Login;

class MergeGuestCart
{
    /**
     * Merging the guest cart to the user's cart upon login to ensure that any items added to the cart while browsing as a guest are not lost 
     * when the user logs in. This listener listens for the Login event and checks if there is a guest cart available. If there is,
     *  it calls the mergeGuestCartToUser method on the CartService to transfer the items from the guest cart to the user's cart.
     * @param Login $event The event instance containing information about the login event, 
     * which can be used if needed to perform additional actions related to merging the guest cart.
     * @return void
     */
    public function handle(Login $event): void
    {
       $cartService = app(CartService::class);

        if ($cartService->hasGuestCart()) {
            $cartService->mergeGuestCartToUser();
        }
    }
}
