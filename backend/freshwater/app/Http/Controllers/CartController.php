<?php
 
namespace App\Http\Controllers;
 
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    private function cartSessionFallbackCacheKey(Request $request): string
    {
        return 'cart-session-fallback:' . sha1(($request->ip() ?? 'unknown') . '|' . (string) $request->userAgent());
    }

    private function cartSessionUserCacheKey(Request $request, string $sessionId): string
    {
        return 'cart-session-user:' . sha1(($request->ip() ?? 'unknown') . '|' . (string) $request->userAgent() . '|' . $sessionId);
    }

    private function frontendCartSessionId(Request $request): ?string
    {
        $sessionId = $request->query('session_id')
            ?: $request->input('session_id')
            ?: $request->query('sessionId')
            ?: $request->input('sessionId')
            ?: $request->query('cartSessionId')
            ?: $request->input('cartSessionId')
            ?: $request->query('cart_session_id')
            ?: $request->input('cart_session_id')
            ?: $request->header('X-Cart-Session-Id')
            ?: $request->header('X-Cart-Session');

        if (! is_scalar($sessionId)) {
            return null;
        }

        $sessionId = trim((string) $sessionId);

        return $sessionId !== '' ? $sessionId : null;
    }

    private function authenticateRememberedCartUser(Request $request, ?string $sessionId): void
    {
        if (Auth::check() || $sessionId === null) {
            return;
        }

        $userId = Cache::store('file')->get($this->cartSessionUserCacheKey($request, $sessionId));

        if (! is_numeric($userId)) {
            return;
        }

        $user = User::find((int) $userId);

        if ($user) {
            Auth::setUser($user);
        }
    }

    /**
     * Resolve CartService using the session_id query param sent by the React client.
     * This bypasses Laravel's cookie-bound session so cross-device carts work correctly.
     * If user is authenticated, ignore session_id and use user cart.
     */
    private function getCartService(Request $request): CartService
    {
        $frontendSessionId = $this->frontendCartSessionId($request);
        $this->authenticateRememberedCartUser($request, $frontendSessionId);

        if (Auth::check()) {
            return new CartService(null);
        }

        return new CartService($frontendSessionId);
    }

    private function logCartState(string $message, Request $request, CartService $cart, array $context = []): void
    {
        $frontendSessionId = $this->frontendCartSessionId($request);
        $itemsCount = $cart->items()->count();

        if (! Auth::check() && $frontendSessionId !== null && $itemsCount > 0) {
            Cache::store('file')->put(
                $this->cartSessionFallbackCacheKey($request),
                $frontendSessionId,
                now()->addMinutes(30)
            );
        }

        Log::info($message, array_merge([
            'frontend_session_id' => $frontendSessionId,
            'resolved_session_id' => $cart->getSessionId(),
            'using_frontend_session' => $frontendSessionId !== null,
            'using_user_cart' => Auth::check(),
            'user_id' => Auth::id(),
            'items_count' => $itemsCount,
            'subtotal' => $cart->subtotal(),
        ], $context));
    }
 
    /**
     * Build a response shape that matches what React's normalizeServerCart expects:
     *   { session_id, items, subtotal }
     */
    private function cartResponse(CartService $cart, ?string $sessionId = null): JsonResponse
    {
        return response()->json([
            'session_id' => $sessionId ?? $cart->getSessionId(),
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
        $response = $this->cartResponse($cart, $this->frontendCartSessionId($request));

        $this->logCartState('Cart show', $request, $cart);

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

        $this->logCartState('Cart add', $request, $cart, [
            'product_id' => $product->id,
            'added_quantity' => (int) ($validated['quantity'] ?? 1),
        ]);
 
        return $this->cartResponse($cart, $this->frontendCartSessionId($request));
    }
 
    /**
     * PATCH /api/cart/update/{product}?session_id=...
     * Body: { quantity }
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
 
        $cart = $this->getCartService($request);
        $beforeItems = $cart->items();

        $this->logCartState('Cart update request', $request, $cart, [
            'product_id' => $product->id,
            'requested_quantity' => (int) $validated['quantity'],
            'cart_item_product_ids_before' => $beforeItems->pluck('product_id')->all(),
            'cart_item_quantities_before' => $beforeItems->pluck('quantity', 'product_id')->all(),
        ]);

        $cart->update($product, (int) $validated['quantity']);
        $response = $this->cartResponse($cart, $this->frontendCartSessionId($request));

        $this->logCartState('Cart update response', $request, $cart, [
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
 
        return $this->cartResponse($cart, $this->frontendCartSessionId($request));
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
