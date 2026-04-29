<?php
 
namespace App\Http\Controllers;
 
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Resolve CartService using the session_id query param sent by the React client.
     * This bypasses Laravel's cookie-bound session so cross-device carts work correctly.
     * If user is authenticated, ignore session_id and use user cart.
     */
    private function getCartService(Request $request): CartService
    {
        if (Auth::check()) {
            return new CartService(null);
        }

        $sessionId = $request->query('session_id')
            ?: $request->input('session_id')
            ?: $request->query('sessionId')
            ?: $request->input('sessionId');

        return new CartService($sessionId ?: null);
    }
 
    /**
     * Build a response shape that matches what React's normalizeServerCart expects:
     *   { session_id, items, subtotal }
     */
    private function cartResponse(CartService $cart): JsonResponse
    {
        return response()->json([
            'session_id' => $cart->getSessionId(),
            'items'      => $cart->items(),
            'subtotal'   => $cart->subtotal(),
        ]);
    }
 
    /**
     * GET /api/cart?session_id=...
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->getCartService($request);
        $response = $this->cartResponse($cart);

        // Debug: log the session_id and items count
        Log::info('Cart show', [
            'session_id' => $cart->getSessionId(),
            'items_count' => $cart->items()->count(),
            'subtotal' => $cart->subtotal(),
        ]);

        return $response;
    }
 
    /**
     * POST /api/cart/add/{product}?session_id=...
     * Body: { quantity }   (price/total are ignored — always taken from the Product model)
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);
 
        $cart = $this->getCartService($request);
        $cart->add($product, (int) ($validated['quantity'] ?? 1));

        Log::info('Cart add', [
            'session_id' => $cart->getSessionId(),
            'product_id' => $product->id,
            'added_quantity' => (int) ($validated['quantity'] ?? 1),
            'cart_items_count' => $cart->items()->count(),
            'subtotal' => $cart->subtotal(),
        ]);
 
        return $this->cartResponse($cart);
    }
 
    /**
     * PATCH /api/cart/update/{product}?session_id=...
     * Body: { quantity }
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);
 
        $cart = $this->getCartService($request);
        $beforeItems = $cart->items();

        Log::info('Cart update request', [
            'session_id' => $cart->getSessionId(),
            'product_id' => $product->id,
            'requested_quantity' => (int) $validated['quantity'],
            'cart_item_product_ids_before' => $beforeItems->pluck('product_id')->all(),
            'cart_item_quantities_before' => $beforeItems->pluck('quantity', 'product_id')->all(),
        ]);

        $cart->update($product, (int) $validated['quantity']);
        $response = $this->cartResponse($cart);

        Log::info('Cart update response', [
            'session_id' => $cart->getSessionId(),
            'product_id' => $product->id,
            'response' => $response->getData(true),
        ]);

        return $response;
    }
 
    /**
     * DELETE /api/cart/delete/{product}?session_id=...
     */
    public function remove(Request $request, Product $product): JsonResponse
    {
        $cart = $this->getCartService($request);
        $cart->remove($product);
 
        return $this->cartResponse($cart);
    }
 
    /**
     * DELETE /api/cart?session_id=...
     * React expects 204 No Content — cartResponse is intentionally NOT called here.
     */
    public function clear(Request $request): Response
    {
        $this->getCartService($request)->clear();
        return response()->noContent();
    }
}
