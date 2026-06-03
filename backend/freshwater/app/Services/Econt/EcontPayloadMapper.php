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
        if (($shipment->direction ?? 'outbound') === 'return') {
            return $this->mapReturnShipment($shipment);
        }

        return $this->mapOutboundShipment($shipment);
    }

    private function mapOutboundShipment(Shipment $shipment): array
    {
        $order = $shipment->order;

        if (! $order) {
            throw new RuntimeException('Пратката няма свързана поръчка.');
        }

        if (empty($order->customer_phone)) {
            throw new RuntimeException('Липсва телефон на клиента за пратката към Еконт.');
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
            'shipmentType' => $this->resolveShipmentType($shipment),
            'sizeUnder60cm' => $this->isSizeUnder60cm($shipment),
            'weight' => round((float) $shipment->weight, 3),
            'packCount' => $shipment->pack_count ?? 1,
            'shipmentDescription' => 'Пратка',
            'payAfterAccept' => false,
            'payAfterTest' => false,
        ];

        $senderOfficeCode = trim((string) config('services.econt.sender.office_code'));
        if ($senderOfficeCode !== '') {
            $payload['senderOfficeCode'] = $senderOfficeCode;
        } else {
            $senderAddress = $this->buildSenderAddress();
            if ($senderAddress) {
                $payload['senderAddress'] = $senderAddress;
            }
        }

        if ($shipment->delivery_type === 'address') {
            $payload['receiverAddress'] = $this->buildAddress($order);
            $payload['sendDate'] = now()->toDateString();
            $payload['holidayDeliveryDay'] = $this->resolveHolidayDeliveryDay();
        } else {
            $payload['receiverOfficeCode'] = $shipment->office_code;
        }

        if ($shipment->cash_on_delivery > 0) {
            $payload['paymentReceiverMethod'] = 'cash';
            $payload['paymentSenderMethod'] = 'bank';
            $payload['services'] = [
                'cdAmount' => round($shipment->cash_on_delivery, 2),
                'cdType' => 'get',
            ];
        }

        if ($shipment->delivery_type !== 'apm') {
            if ($shipment->declared_value > 0) {
                $payload['services']['declaredValueAmount'] = round($shipment->declared_value, 2);
            } elseif (! empty($order->subtotal) && $order->subtotal > 0) {
                $payload['services']['declaredValueAmount'] = round($order->subtotal, 2);
            }
        }

        if (! empty($order->customer_email)) {
            $payload['services']['smsNotification'] = [
                'toEmail' => $order->customer_email,
            ];
        }

        $settings = Setting::current();
        $freeDelivery = $settings->delivery_enabled
            && $settings->free_delivery_over !== null
            && $order->subtotal >= $settings->free_delivery_over;

        if ($freeDelivery) {
            $payload['paymentSenderMethod'] = 'cash';
            unset($payload['paymentReceiverMethod']);
        }

        return $payload;
    }

    private function mapReturnShipment(Shipment $shipment): array
    {
        $order = $shipment->order;

        if (! $order) {
            throw new RuntimeException('Пратката няма свързана поръчка.');
        }

        if (empty($order->customer_phone)) {
            throw new RuntimeException('Липсва телефон на клиента за обратната пратка към Еконт.');
        }

        $phone = $this->formatPhone($order->customer_phone);

        $payload = [
            'senderClient' => [
                'name' => $order->customer_name,
                'phones' => [$phone],
            ],
            'receiverClient' => [
                'name' => config('services.econt.sender.name'),
                'phones' => [config('services.econt.sender.phone')],
            ],
            'shipmentType' => $this->resolveShipmentType($shipment),
            'sizeUnder60cm' => $this->isSizeUnder60cm($shipment),
            'weight' => round((float) $shipment->weight, 3),
            'packCount' => $shipment->pack_count ?? 1,
            'shipmentDescription' => 'Връщане по поръчка #'.$order->id,
            'payAfterAccept' => false,
            'payAfterTest' => false,
            'paymentReceiverMethod' => 'cash',
        ];

        if ($shipment->delivery_type === 'address') {
            $payload['senderAddress'] = $this->buildAddress($order);
        } else {
            if (empty($shipment->office_code)) {
                throw new RuntimeException('Липсва офис на клиента за обратната пратка към Еконт.');
            }

            $payload['senderOfficeCode'] = $shipment->office_code;
        }

        $receiverOfficeCode = trim((string) config('services.econt.sender.office_code'));
        if ($receiverOfficeCode !== '') {
            $payload['receiverOfficeCode'] = $receiverOfficeCode;
        } else {
            $receiverAddress = $this->buildSenderAddress();

            if (! $receiverAddress) {
                throw new RuntimeException('Липсва конфигурация за адреса на търговеца в Еконт.');
            }

            $payload['receiverAddress'] = $receiverAddress;
        }

        if ($shipment->delivery_type !== 'apm') {
            if ($shipment->declared_value > 0) {
                $payload['services']['declaredValueAmount'] = round($shipment->declared_value, 2);
            } elseif (! empty($order->subtotal) && $order->subtotal > 0) {
                $payload['services']['declaredValueAmount'] = round($order->subtotal, 2);
            }
        }

        if (! empty($order->customer_email)) {
            $payload['services']['smsNotification'] = [
                'toEmail' => $order->customer_email,
            ];
        }

        return $payload;
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);

        if (! str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+359'.substr($phone, 1);
            } else {
                $phone = '+359'.$phone;
            }
        }

        return $phone;
    }

    private function buildAddress(object $order): array
    {
        $cityName = trim((string) $order->shipping_city);
        $postCode = trim((string) ($order->shipping_postcode ?? ''));
        $street = trim((string) ($order->shipping_address ?? ''));

        if ($cityName === '' || $postCode === '' || $street === '') {
            Log::error('Econt receiver address missing required fields', [
                'order_id' => $order->id ?? null,
                'city' => $cityName,
                'postcode' => $postCode,
                'street' => $street,
            ]);

            throw new RuntimeException('Адресът на получателя за пратката към Еконт е непълен.');
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

        if (! empty($order->shipping_address_num)) {
            $address['num'] = trim((string) $order->shipping_address_num);
        }

        if (! empty($order->shipping_address_quarter)) {
            $address['quarter'] = trim((string) $order->shipping_address_quarter);
        } elseif (! empty($order->shipping_quarter)) {
            $address['quarter'] = trim((string) $order->shipping_quarter);
        }

        if (! empty($order->shipping_address_details)) {
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

    private function resolveHolidayDeliveryDay(): string
    {
        return 'workday';
    }

    private function resolveShipmentType(Shipment $shipment): string
    {
        return $shipment->shipment_type === 'cargo'
            ? 'CARGO'
            : 'PACK';
    }

    private function isSizeUnder60cm(Shipment $shipment): bool
    {
        $limit = (float) config('services.econt.cargo_dimension_from_cm', 60);

        $width = (float) ($shipment->width ?? 0);
        $height = (float) ($shipment->height ?? 0);
        $length = (float) ($shipment->length ?? 0);

        if ($width === 0.0 && $height === 0.0 && $length === 0.0) {
            return true;
        }

        return $width < $limit && $height < $limit && $length < $limit;
    }
}
