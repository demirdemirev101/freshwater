<?php

namespace App\Services;

use App\Exceptions\EmptyCartException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Econt\EcontCityResolverService;
use App\Support\SensitiveValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CheckoutSupportService
{
    public function __construct(
        private SettingsService $settingsService,
        private EcontCityResolverService $econtCityResolverService
    ) {}

    public function officesForCity(string $city): Collection
    {
        try {
            return collect($this->econtCityResolverService->getOffices($city))
                ->filter(fn ($office) => is_array($office))
                ->map(fn (array $office) => $this->normalizeEcontOffice($office))
                ->filter(fn (array $office) => ! empty($office['code']) && ! empty($office['name']))
                ->values();
        } catch (\Throwable $e) {
            Log::error('Econt offices endpoint failed', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    public function estimateShipping(array $validated, CartService $cartService, ?string $requestedSessionId, ?int $userId = null): array
    {
        $cartItems = $cartService->items();
        $requestItems = ! empty($validated['items'])
            ? $this->buildItemsFromRequest($validated['items'])
            : collect();
        $effectiveItems = $cartItems->isNotEmpty() ? $cartItems : $requestItems;
        $effectiveSubtotal = $cartItems->isNotEmpty()
            ? $cartService->subtotal()
            : (float) $requestItems->sum('total');

        if ($effectiveItems->isEmpty()) {
            Log::warning('calculate shipping skipped because cart is empty', [
                'requested_session_id_fingerprint' => SensitiveValue::fingerprint($requestedSessionId),
                'resolved_session_id_fingerprint' => SensitiveValue::fingerprint($cartService->getSessionId()),
                'user_id' => $userId,
                'shipping_method' => $validated['shipping_method'],
                'shipping_city' => $validated['shipping_city'],
            ]);

            throw new EmptyCartException('Количката е празна.');
        }

        $tempOrder = $this->buildEstimateOrder($validated, $effectiveItems, $effectiveSubtotal);
        $tempOrder->shipping_price = $this->settingsService->estimateShipping($tempOrder);

        Log::info('calculate shipping price', [
            'requested_session_id_fingerprint' => SensitiveValue::fingerprint($requestedSessionId),
            'resolved_session_id_fingerprint' => SensitiveValue::fingerprint($cartService->getSessionId()),
            'user_id' => $userId,
            'subtotal' => $effectiveSubtotal,
            'shipping_price' => $tempOrder->shipping_price ?? 0.0,
            'order_id' => $tempOrder->id,
            'items_count' => $effectiveItems->count(),
            'items_source' => $cartItems->isNotEmpty() ? 'cart' : 'request',
            'econt_office_code' => $tempOrder->econt_office_code,
            'shipping_method' => $tempOrder->shipping_method,
            'payment_method' => $tempOrder->payment_method,
        ]);

        return [
            'shipping_price' => $tempOrder->shipping_price ?? 0.0,
        ];
    }

    private function buildEstimateOrder(array $validated, Collection $effectiveItems, float $effectiveSubtotal): Order
    {
        $tempOrder = new Order([
            'customer_name' => $validated['customer_name'] ?? 'Изчисляване на доставка',
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

        return $tempOrder;
    }

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
}
