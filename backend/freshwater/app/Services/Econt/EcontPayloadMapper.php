<?php

namespace App\Services\Econt;

use App\Models\Setting;
use App\Models\Shipment;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EcontPayloadMapper
{
    public function map(Shipment $shipment): array
    {
        $order = $shipment->order;

        if (!$order) {
            throw new RuntimeException('Shipment has no related order.');
        }

        if (empty($order->customer_phone)) {
            throw new RuntimeException('Missing customer phone for Econt shipment.');
        }

        $phone = $this->formatPhone($order->customer_phone);

        $payload = [
            'senderClient' => [
                'name' => config('services.econt.sender.name'),
                'phones' => [config('services.econt.sender.phone')],
            ],

            'receiverClient' => [
                'name' => $order->customer_name,
                'phones' => [$phone],
            ],

            'shipmentType' => 'PACK',
            'weight' => round($shipment->weight, 3),
            'packCount' => $shipment->pack_count ?? 1,
            'shipmentDescription' => 'Package',

            'payAfterAccept' => false,
            'payAfterTest' => false,
        ];

        $senderOfficeCode = config('services.econt.sender.office_code');
        if (!empty($senderOfficeCode)) {
            $payload['senderOfficeCode'] = $senderOfficeCode;
        } else {
            $senderAddress = $this->buildSenderAddress();
            if ($senderAddress) {
                $payload['senderAddress'] = $senderAddress;
            }
        }

        // Receiver information based on delivery type
        if ($shipment->delivery_type === 'address') {
            $payload['receiverAddress'] = $this->buildAddress($order);
            $payload['sendDate'] = now()->toDateString();
            $payload['holidayDeliveryDay'] = $this->resolveHolidayDeliveryDay($order);
        } else {
            $payload['receiverOfficeCode'] = $shipment->office_code;
        }

        // Payment and services
        if ($shipment->cash_on_delivery > 0) {
            $payload['paymentReceiverMethod'] = 'cash';
            $payload['paymentSenderMethod'] = 'bank';
            $payload['services'] = [
                'cdAmount' => round($shipment->cash_on_delivery, 2),
                'cdType' => 'get',
            ];
        }

        // Declared value
        if ($shipment->declared_value > 0) {
            $payload['services']['declaredValueAmount'] = round($shipment->declared_value, 2);
        } elseif (!empty($order->subtotal) && $order->subtotal > 0) {
            $payload['services']['declaredValueAmount'] = round($order->subtotal, 2);
        }

        // SMS notification
        if (!empty($order->customer_email)) {
            $payload['services']['smsNotification'] = [
                'toEmail' => $order->customer_email,
            ];
        }

        // Free delivery -> sender pays courier service
        // Only applies to non-COD orders — for COD, paymentSenderMethod must remain 'bank'
        $settings = Setting::current();
        $freeDelivery = $settings->delivery_enabled
            && $settings->free_delivery_over !== null
            && $order->subtotal >= $settings->free_delivery_over;

        if ($freeDelivery && $shipment->cash_on_delivery <= 0) {
            $payload['paymentSenderMethod'] = 'cash';
            unset($payload['paymentReceiverMethod']);
        }

        return $payload;
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);

        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+359' . substr($phone, 1);
            } else {
                $phone = '+359' . $phone;
            }
        }

        return $phone;
    }

    private function buildAddress(object $order): array
    {
        $cityName = trim($order->shipping_city);
        $postCode = trim((string) ($order->shipping_postcode ?? ''));
        $street = trim($order->shipping_address ?? '');

        if ($cityName === '' || $postCode === '' || $street === '') {
            Log::error('Econt receiver address missing required fields', [
                'order_id' => $order->id ?? null,
                'city' => $cityName,
                'postcode' => $postCode,
                'street' => $street,
            ]);

            throw new RuntimeException('Incomplete receiver address for Econt shipment.');
        }

        $address = [
            'city' => [
                'country' => [
                    'code3' => 'BGR',
                ],
                'name' => $cityName,
                'postCode' => $postCode,
            ],
            'street' => $street,
        ];

        if (!empty($order->shipping_address_num)) {
            $address['num'] = trim((string) $order->shipping_address_num);
        }

        if (!empty($order->shipping_address_quarter)) {
            $address['quarter'] = trim((string) $order->shipping_address_quarter);
        } elseif (!empty($order->shipping_quarter)) {
            $address['quarter'] = trim((string) $order->shipping_quarter);
        }

        if (!empty($order->shipping_address_details)) {
            $address['other'] = trim((string) $order->shipping_address_details);
        }

        Log::info('Econt receiver address built', [
            'order_id' => $order->id ?? null,
            'city' => $cityName,
            'postcode' => $postCode,
        ]);

        return $address;
    }

    private function buildSenderAddress(): ?array
    {
        $cityName = trim((string) config('services.econt.sender.city'));
        $postCode = trim((string) config('services.econt.sender.postcode'));
        $street = trim((string) config('services.econt.sender.street'));

        if ($cityName === '' || $postCode === '' || $street === '') {
            return null;
        }

        $address = [
            'city' => [
                'country' => [
                    'code3' => 'BGR',
                ],
                'name' => $cityName,
                'postCode' => $postCode,
            ],
            'street' => $street,
        ];

        $num = trim((string) config('services.econt.sender.num'));
        if ($num !== '') {
            $address['num'] = $num;
        }

        return $address;
    }

    private function resolveHolidayDeliveryDay(object $order): string
    {
        $configured = $order->holiday_delivery_day ?? null;

        if (!empty($configured)) {
            if ($configured instanceof \DateTimeInterface) {
                return $configured->format('Y-m-d');
            }

            return (string) $configured;
        }

        $date = now()->addDay();

        while (in_array($date->dayOfWeekIso, [6, 7], true)) {
            $date->addDay();
        }

        return $date->toDateString();
    }
}