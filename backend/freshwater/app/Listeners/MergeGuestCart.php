<?php

namespace App\Listeners;

use App\Services\CartService;
use Illuminate\Auth\Events\Login;

class MergeGuestCart
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
       $cartService = app(CartService::class);

        if ($cartService->hasGuestCart()) {
            $cartService->mergeGuestCartToUser();
        }
    }
}
