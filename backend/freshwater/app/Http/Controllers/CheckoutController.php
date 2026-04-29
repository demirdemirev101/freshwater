<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\Econt\EcontCityResolverService;
use App\Services\OrderService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Resolve CartService using the session_id/sessionId sent by the React client.
     * This keeps checkout pricing aligned with the cart endpoints instead of falling back to Laravel's cookie session ID.
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
     * Build temporary order items from request payload when the React page has not synced a server cart yet.
     */
    private function buildItemsFromRequest(array $items): Collection
    {
        $products = Product::query()
            ->whereIn('id', collect($items)->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        return collect($items)->map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $price = (float) ($product?->sale_price ?? $product?->price ?? 0);

            $orderItem = new OrderItem([
                'product_id' => $item['product_id'],
                'price' => $price,
                'quantity' => $quantity,
                'total' => $price * $quantity,
            ]);
            $orderItem->setRelation('product', $product);

            return $orderItem;
        });
    }

    /**
     * Normalize raw Econt office payloads into a stable frontend-friendly shape.
     */
    private function normalizeEcontOffice(array $office): array
    {
        $code = $office['code']
            ?? $office['officeCode']
            ?? $office['office_code']
            ?? $office['id']
            ?? null;

        $name = $office['name']
            ?? $office['officeName']
            ?? $office['office_name']
            ?? $office['fullName']
            ?? null;

        $city = $office['city']
            ?? $office['cityName']
            ?? data_get($office, 'address.city.name')
            ?? data_get($office, 'address.cityName')
            ?? null;

        $address = $office['address']
            ?? $office['fullAddress']
            ?? $office['addressLine']
            ?? $office['streetAddress']
            ?? null;

        if (is_array($address)) {
            $address = $address['fullAddress']
                ?? $address['addressLine']
                ?? $address['streetAddress']
                ?? trim(implode(' ', array_filter([
                    $address['street'] ?? null,
                    $address['num'] ?? null,
                    $address['quarter'] ?? null,
                ])));
        }

        return [
            'code' => $code !== null ? (string) $code : null,
            'name' => $name !== null ? (string) $name : null,
            'city' => $city !== null ? (string) $city : null,
            'address' => is_string($address) && trim($address) !== '' ? trim($address) : null,
        ];
    }

    /**
     * Return Econt offices for a city in a stable JSON shape used by the React checkout.
     */
    public function econtOffices(Request $request, EcontCityResolverService $econtCityResolverService)
    {
        $validated = $request->validate([
            'city' => 'required|string',
        ]);

        $city = trim($validated['city']);
        try {
            $offices = collect($econtCityResolverService->getOffices($city))
                ->filter(fn ($office) => is_array($office))
                ->map(fn (array $office) => $this->normalizeEcontOffice($office))
                ->filter(fn (array $office) => ! empty($office['code']) && ! empty($office['name']))
                ->values();
        } catch (\Throwable $e) {
            Log::error('Econt offices endpoint failed', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            $offices = collect();
        }

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
    public function calculateShipping(Request $request, SettingsService $settingsService)
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
            'sessionId' => 'sometimes|string',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $cartService = $this->getCartService($request);
        $requestedSessionId = $request->query('session_id')
            ?: $request->input('session_id')
            ?: $request->query('sessionId')
            ?: $request->input('sessionId');
        $cartItems = $cartService->items();
        $requestItems = ! empty($validated['items'])
            ? $this->buildItemsFromRequest($validated['items'])
            : collect();
        $effectiveItems = $cartItems->isNotEmpty() ? $cartItems : $requestItems;
        $effectiveSubtotal = $cartItems->isNotEmpty()
            ? $cartService->subtotal()
            : (float) $requestItems->sum('total');

        $tempOrder = new Order([
            'customer_name' => $validated['customer_name'] ?? 'Shipping Estimate',
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? (string) config('services.econt.sender.phone', '0000000000'),
            'subtotal' => $effectiveSubtotal,
            'shipping_method' => $validated['shipping_method'],
            'shipping_address' => $validated['shipping_address'] ?? '',
            'shipping_city' => $validated['shipping_city'],
            'shipping_postcode' => $validated['shipping_postcode'] ?? null,
            'econt_office_code' => $validated['shipping_method'] === 'address'
                ? null
                : ($validated['econt_office_code'] ?? null),
            'payment_method' => $validated['payment_method'] ?? null,
        ]);
        $tempOrder->setRelation('items', $effectiveItems);

        if ($effectiveItems->isEmpty()) {
            Log::warning('calculate shipping skipped because cart is empty', [
                'requested_session_id' => $requestedSessionId,
                'resolved_session_id' => $cartService->getSessionId(),
                'shipping_method' => $validated['shipping_method'],
                'shipping_city' => $validated['shipping_city'],
            ]);

            return response()->json([
                'shipping_price' => 0.0,
                'message' => 'Cart is empty.',
            ], 422);
        }

        // Use a live estimate here so the checkout UI can show Econt pricing
        // even for payment methods that are deferred in the final order flow.
        $tempOrder->shipping_price = $settingsService->estimateShipping($tempOrder);

        Log::info('calculate shipping price', [
            'requested_session_id' => $requestedSessionId,
            'resolved_session_id' => $cartService->getSessionId(),
            'subtotal' => $effectiveSubtotal,
            'shipping_price' => $tempOrder->shipping_price ?? 0.0,
            'order_id' => $tempOrder->id,
            'items_count' => $effectiveItems->count(),
            'items_source' => $cartItems->isNotEmpty() ? 'cart' : 'request',
            'econt_office_code' => $tempOrder->econt_office_code,
            'shipping_method' => $tempOrder->shipping_method,
            'payment_method' => $tempOrder->payment_method,
        ]);

        return response()->json([
            'shipping_price' => $tempOrder->shipping_price ?? 0.0,
        ]);
    }
    /**
     * Process the checkout by validating the request data and creating an order using the OrderService.
      * @param \Illuminate\Http\Request $request
      * @param \App\Services\OrderService $orderService
      * @return \Illuminate\Http\JsonResponse
      * @throws \App\Exceptions\CheckoutException
     */
    public function store(Request $request, OrderService $orderService) 
    {
        $validated = $request->validate([
            'customer_name'     => 'required|string',
            'customer_email'    => 'required|email',
            'customer_phone'    => 'nullable|string',
            'shipping_method'   => 'required|in:address,office,apm',
            'shipping_address'  => 'required_if:shipping_method,address|string',
            'shipping_city'     => 'required|string',
            'shipping_postcode' => 'nullable|string',
            'econt_office_code' => 'required_if:shipping_method,office,apm|string',
            'payment_method'    => 'required|string',
            'session_id'       => 'sometimes|string',
            'sessionId'        => 'sometimes|string',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
        ]);
        $validated['session_id'] = $request->query('session_id')
            ?: $request->input('session_id')
            ?: $request->query('sessionId')
            ?: $request->input('sessionId');

        try {
            $order = $orderService->createFromItems($validated);

            return response()->json([
                'success'  => true,
                'order_id' => $order->id,
            ]);

        } catch (CheckoutException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
