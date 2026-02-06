<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * The CartService class manages the shopping cart functionality for both authenticated users and guests.
 *  It provides methods to add, update, and remove products from the cart, as well as to retrieve cart items and calculate the subtotal.
 *  The service automatically resolves the appropriate cart based on the user's authentication status and session,
 *  ensuring a seamless shopping experience for all users.
 */

class CartService
{   
    /**
     * The cart instance associated with the current user or session.
     */
    protected Cart $cart;
    
    /**
     * CartService constructor. It initializes the cart property by resolving the current cart based on the user's authentication status and session.
     */
    public function __construct()
    {
        $this->cart=$this->resolveCart();
    }
    /**
     * Resolve the current cart based on the user's authentication status and session.
     *  If the user is authenticated, it retrieves or creates a cart associated with the user's ID. If the user is a guest,
     *  it retrieves or creates a cart associated with the session ID. This method ensures that each user has a unique cart,
     *  whether they are logged in or browsing as a guest.
     */
    protected function resolveCart(): Cart
    {
        if(Auth::check()){
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $sessionId=Session::getId();

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    /**
     * Add a product to the cart with the specified quantity. If the product already exists in the cart,
     *  it updates the quantity and total price. The method uses a database transaction to ensure data integrity during the add operation.
     *  It checks if the product is already in the cart, and if so, it increments the quantity and updates the total price. If the product is not in the cart,
     *  it creates a new cart item with the specified product details. 
     */
    public function add(Product $product, int $quantity=1): void
    {
        DB::transaction(function () use ($product, $quantity)
        {
            $item=$this->cart->items()
                ->where('product_id', $product->id)
                ->first();

            $price=$product->sale_price??$product->price;

            if($item){
                $item->quantity+=$quantity;
                $item->total=$item->quantity*$price;
                $item->save();
            } else {
                $this->cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $quantity*$price,
                ]);
            }
        });
    }

    /**
     * Update the quantity of a specific product in the cart. If the quantity is set to zero or less, it removes the product from the cart.
     *  The method checks if the specified quantity is less than or equal to zero, and if so, it calls the remove method to delete the product from the cart.
     *  Otherwise, it updates the existing cart item with the new quantity and recalculates the total price based on the product's price.
     *  This method ensures that the cart remains accurate and up-to-date with the user's desired quantities for each product.
     */
    public function update(Product $product, int $quantity): void
    {
        if($quantity<=0){
            $this->remove($product);
            return;
        }

        $price = $product->sale_price ?? $product->price;

        $this->cart->items()
            ->where('product_id', $product->id)
            ->update([
                'quantity' => $quantity,
                'total' => $quantity * $price,
            ]);
    }

    /**
     * Remove a specific product from the cart. The method deletes the cart item associated with the given product ID from the cart's items.
     */
    public function remove(Product $product): void
    {
        $this->cart->items()
            ->where('product_id', $product->id)
            ->delete();
    }
    /**
     * Return all items in the cart with their associated product details.
     */
    public function items()
    {
        return $this->cart->items()->with('product')->get();
    }

    /**
     * Clear all items from the cart. The method checks wich cart is being used (user or session) and deletes all items from that cart.
     *  This effectively empties the cart for the user or guest.
     */
    public function clear(): void
    {
        if (Auth::check()) {
            Cart::where('user_id', Auth::id())
                ->first()?->items()->delete();
        }

        Cart::where('session_id', session()->getId())
            ->first()?->items()->delete();
    }
    /**
     * Calculate the subtotal of the cart by summing the total price of all items in the cart.
     */
    public function subtotal(): float
    {
        return (float) $this->cart->items()->sum('total');
    }

    /**
     * Return the current cart instance.
     */
    public function cart(): Cart
    {
        return $this->cart;
    }
    /**
     * Merge the guest cart with the authenticated user's cart upon login. If a guest cart exists for the current session,
     * it checks if the user already has a cart.
     */
    public function mergeGuestCartToUser(): void
    {
        if(!Auth::check()){
            return;
        }

        $sessionId = Session::getId();

        $guestCart = Cart::where('session_id', $sessionId)->first();
        $userCart = Cart::where('user_id', Auth::id())->first();


        if(!$guestCart){
            return;
        }

        DB::transaction(function() use ($guestCart, $userCart){
            if(!$userCart){
                $guestCart->update([
                    'user_id' => Auth::id(),
                    'session_id' => null,
                ]);

                return;
            }

            foreach($guestCart->items as $guestItem){

                $userItem = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if($userItem){
                    $userItem->quantity+=$guestItem->quantity;
                    $userItem->total = $userItem->quantity * $userItem->price;
                    $userItem->save();
                } else {
                    $userCart->items()->create([
                        'product_id' => $guestItem->product_id,
                        'quantity' => $guestItem->quantity,
                        'price' => $guestItem -> price,
                        'total' => $guestItem->total,
                    ]);
                }
            }
            
            $guestCart->items()->delete();
            $guestCart->delete();

        });
    }
    /**
     * Check if there is a guest cart associated with the current session.
     */
    public function hasGuestCart(): bool
    {
        return Cart::where('session_id', session()->getId())->exists();
    }
}