<?php

namespace App\Services;

use App\Models\Order;

class OrderDeliveryDetailsService
{
    public function forEmail(Order $order): array
    {
        $shippingMethod = $order->shipping_method ?? 'address';

        if ($shippingMethod === 'address') {
            return [
                'title' => 'Адрес за доставка',
                'lines' => array_values(array_filter([
                    $this->nullableTrim($order->shipping_address),
                    $this->joinParts([
                        $this->nullableTrim($order->shipping_postcode),
                        $this->nullableTrim($order->shipping_city),
                    ]),
                ])),
                'summary' => $this->joinParts([
                    $this->nullableTrim($order->shipping_address),
                    $this->nullableTrim($order->shipping_postcode),
                    $this->nullableTrim($order->shipping_city),
                ]),
            ];
        }

        $title = $shippingMethod === 'apm'
            ? 'Автомат на Еконт'
            : 'Офис на Еконт';

        $lines = array_values(array_filter([
            $this->nullableTrim($order->shipping_address),
            $this->nullableTrim($order->econt_office_code)
                ? 'Код: '.$this->nullableTrim($order->econt_office_code)
                : null,
            $this->joinParts([
                $this->nullableTrim($order->shipping_postcode),
                $this->nullableTrim($order->shipping_city),
            ]),
        ]));

        return [
            'title' => $title,
            'lines' => $lines,
            'summary' => $this->joinParts($lines),
        ];
    }

    private function joinParts(array $parts): ?string
    {
        $parts = array_values(array_filter($parts, fn (?string $part) => $part !== null && $part !== ''));

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
