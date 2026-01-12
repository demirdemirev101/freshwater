<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected Cart $cart;

    public function __construct()
    {
        $this->cart=$this->resolveCart();
    }

    protected function resolveCart(): Cart
    {
        if(Auth::check()){
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $sessionId=Session::getId();

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

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

    public function remove(Product $product): void
    {
        $this->cart->items()
            ->where('product_id', $product->id)
            ->delete();
    }

    public function items()
    {
        return $this->cart->items()->with('product')->get();
    }

    public function clear(): void
    {
        $this->cart->items()->delete();
    }

    public function subtotal(): float
    {
        return (float) $this->cart->items()->sum('total');
    }

    public function cart(): Cart
    {
        return $this->cart;
    }

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

    public function hasGuestCart(): bool
    {
        return Cart::where('session_id', session()->getId())->exists();
    }
}