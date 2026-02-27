<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private CartService $cart;

    // Ensure the cart service is available for all methods in this controller
    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Helper method to return the current cart contents and subtotal as a JSON response.
     */
    private function cartResponse()
    {
        return response()->json([
            'items' => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    /**
     * Display the current contents of the cart, including items and subtotal.
      *
      * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        return $this->cartResponse();
    }

    /**
     * Add a product to the cart with an optional quantity (defaulting to 1 if not provided).
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity'   => 'nullable|integer|min:1',
        ]);
        
        $quantity = (int)($validated['quantity']);
        
        $this->cart->add($product, $quantity);

        return $this->cartResponse();
    }

    /**
     * Update the quantity of a specific product in the cart, or remove it if the quantity is set to zero.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity'   => 'required|integer|min:0',
        ]);

        $quantity = (int)($validated['quantity']);

        $this->cart->update($product, $quantity);

        return $this->cartResponse();
    }

    /**
     * Remove a specific product from the cart entirely.
     */
    public function remove(Product $product)
    {
        $this->cart->remove($product);

        return $this->cartResponse();
    }

    /**
     * Clear all items from the cart, resetting it to an empty state.
     */
    public function clear()
    {
        $this->cart->clear();
        return response()->noContent();
    }
}
