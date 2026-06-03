<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Exceptions\EmptyCartException;
use App\Services\CartService;
use App\Services\CartSessionResolverService;
use App\Services\CheckoutSupportService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    private function getCartService(Request $request, CartSessionResolverService $cartSessionResolver): CartService
    {
        if (Auth::check()) {
            return new CartService(null);
        }

        return new CartService($cartSessionResolver->rememberFromRequest($request));
    }

    /**
     * Return Econt offices for a city in a stable JSON shape used by the React checkout.
     */
    public function econtOffices(Request $request, CheckoutSupportService $checkoutSupportService)
    {
        $validated = $request->validate([
            'city' => 'required|string',
        ]);

        $city = trim($validated['city']);
        $offices = $checkoutSupportService->officesForCity($city);

        Log::info('Econt offices lookup response', [
            'city' => $city,
            'count' => $offices->count(),
        ]);

        return response()->json([
            'offices' => $offices,
        ]);
    }

    /**
     * Calculate shipping cost for the current cart based on shipping address.
     * This is used by frontend to show shipping cost before checkout.
     */
    public function calculateShipping(
        Request $request,
        CheckoutSupportService $checkoutSupportService,
        CartSessionResolverService $cartSessionResolver
    )
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'shipping_method' => 'required|in:address,office,apm',
            'shipping_address' => 'required_if:shipping_method,address|string',
            'shipping_city' => 'required|string',
            'shipping_postcode' => 'nullable|string',
            'econt_office_code' => 'required_if:shipping_method,office,apm|string|nullable',
            'payment_method' => 'nullable|string',
            'session_id' => 'sometimes|string',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $requestedSessionId = $cartSessionResolver->rememberFromRequest($request);
        $cartService = $this->getCartService($request, $cartSessionResolver);

        try {
            return response()->json(
                $checkoutSupportService->estimateShipping($validated, $cartService, $requestedSessionId, Auth::id())
            );
        } catch (EmptyCartException $e) {
            return response()->json([
                'shipping_price' => 0.0,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process the checkout by validating the request data and creating an order using the OrderService.
     *
     * @return JsonResponse
     *
     * @throws CheckoutException
     */
    public function store(
        Request $request,
        OrderService $orderService,
        CartSessionResolverService $cartSessionResolver
    )
    {
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string',
            'shipping_method' => 'required|in:address,office,apm',
            'shipping_address' => 'required_if:shipping_method,address|string',
            'shipping_city' => 'required|string',
            'shipping_postcode' => 'nullable|string',
            'econt_office_code' => 'required_if:shipping_method,office,apm|string',
            'payment_method' => 'required|in:bank_transfer,cod',
            'session_id' => 'sometimes|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        $validated['session_id'] = $cartSessionResolver->rememberFromRequest($request);

        try {
            $order = $orderService->createFromItems($validated);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
            ]);

        } catch (CheckoutException $e) {
            Log::error('Checkout failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Възникна неочаквана грешка при завършване на поръчката. Моля, опитайте отново по-късно.',
            ], $e->getCode() ?: 422);

        } catch (\Exception $e) {
            Log::error('Checkout failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Възникна неочаквана грешка при завършване на поръчката. Моля, опитайте отново по-късно.',
            ], 500);
        }
    }
}
