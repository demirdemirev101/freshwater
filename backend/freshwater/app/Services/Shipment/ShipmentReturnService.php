<?php

namespace App\Services\Shipment;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\Econt\EcontService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShipmentReturnService
{
    public function __construct(
        private EcontService $econtService
    ) {}

    public function createReturnLabel(Order $order): Shipment
    {
        $order->loadMissing('shipment');

        $shipment = $order->shipment;

        if (! $shipment) {
            throw new RuntimeException('Поръчката няма пратка за връщане.');
        }

        if (! empty($shipment->return_carrier_shipment_id)) {
            throw new RuntimeException('За тази поръчка вече има създадена обратна пратка.');
        }

        $payload = $this->buildPayload($order, $shipment);

        $shipment->update([
            'return_carrier_payload' => $payload,
            'return_status' => 'pending',
            'return_error_message' => null,
        ]);

        if (! config('services.econt.enabled')) {
            $testNumber = 'TEST-RETURN-'.$shipment->id;

            $shipment->update([
                'return_carrier_response' => [
                    'message' => 'Еконт е изключен в локалната среда.',
                    'label' => [
                        'shipmentNumber' => $testNumber,
                    ],
                ],
                'return_carrier_shipment_id' => $testNumber,
                'return_tracking_number' => $testNumber,
                'return_status' => 'confirmed',
                'return_sent_to_carrier_at' => now(),
            ]);

            return $shipment->fresh();
        }

        try {
            $response = $this->econtService->createLabel($payload);
            $label = $response['label'] ?? null;

            if (! is_array($label) || empty($label['shipmentNumber'])) {
                throw new RuntimeException('Невалиден отговор от Еконт: липсва номер на обратната пратка.');
            }

            $shipment->update([
                'return_carrier_response' => $response,
                'return_carrier_shipment_id' => $label['shipmentNumber'],
                'return_tracking_number' => $label['shipmentNumber'],
                'return_label_url' => $label['pdfURL'] ?? null,
                'return_status' => 'confirmed',
                'return_sent_to_carrier_at' => now(),
                'return_error_message' => null,
            ]);

            Log::info('Return shipment created', [
                'order_id' => $order->id,
                'shipment_id' => $shipment->id,
                'return_tracking_number' => $label['shipmentNumber'],
            ]);

            return $shipment->fresh();
        } catch (\Throwable $e) {
            $shipment->update([
                'return_status' => 'error',
                'return_error_message' => $e->getMessage(),
            ]);

            Log::error('Return shipment creation failed', [
                'order_id' => $order->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildPayload(Order $order, Shipment $shipment): array
    {
        if (empty($order->customer_name)) {
            throw new RuntimeException('Липсва име на клиента за обратната пратка.');
        }

        if (empty($order->customer_phone)) {
            throw new RuntimeException('Липсва телефон на клиента за обратната пратка.');
        }

        $payload = [
            'previousShipmentNumber' => $shipment->carrier_shipment_id,
            'previousShipmentReceiverPhone' => $this->formatPhone($order->customer_phone),
            'senderClient' => [
                'name' => $order->customer_name,
                'phones' => [$this->formatPhone($order->customer_phone)],
            ],
            'receiverClient' => [
                'name' => config('services.econt.sender.name'),
                'phones' => [(string) config('services.econt.sender.phone')],
            ],
            'shipmentType' => $shipment->shipment_type === 'cargo' ? 'cargo' : 'pack',
            'weight' => round((float) ($shipment->weight ?: 1), 3),
            'packCount' => max(1, (int) ($shipment->pack_count ?: 1)),
            'shipmentDescription' => 'Връщане по поръчка #'.$order->id,
            'sendDate' => now()->toDateString(),
            'holidayDeliveryDay' => 'workday',
            'payAfterAccept' => false,
            'payAfterTest' => false,
            'paymentReceiverMethod' => 'cash',
        ];

        $this->applySenderLocation($payload, $order, $shipment);
        $this->applyReceiverLocation($payload);

        return array_filter($payload, static fn ($value) => $value !== null);
    }

    private function applySenderLocation(array &$payload, Order $order, Shipment $shipment): void
    {
        if ($shipment->delivery_type === 'address') {
            $payload['senderAddress'] = $this->buildOrderAddress($order);

            return;
        }

        $officeCode = $shipment->office_code ?: $order->econt_office_code;

        if (empty($officeCode)) {
            throw new RuntimeException('Липсва код на офис за подателя на обратната пратка.');
        }

        $payload['senderOfficeCode'] = $officeCode;
    }

    private function applyReceiverLocation(array &$payload): void
    {
        $receiverOfficeCode = trim((string) config('services.econt.sender.office_code'));

        if ($receiverOfficeCode !== '') {
            $payload['receiverOfficeCode'] = $receiverOfficeCode;

            return;
        }

        $payload['receiverAddress'] = $this->buildMerchantAddress();
    }

    private function buildOrderAddress(Order $order): array
    {
        $city = trim((string) $order->shipping_city);
        $postCode = trim((string) $order->shipping_postcode);
        $street = trim((string) $order->shipping_address);

        if ($city === '' || $postCode === '' || $street === '') {
            throw new RuntimeException('Адресът на клиента за обратната пратка е непълен.');
        }

        return [
            'city' => [
                'country' => [
                    'code3' => 'BGR',
                ],
                'name' => $city,
                'postCode' => $postCode,
            ],
            'street' => $street,
        ];
    }

    private function buildMerchantAddress(): array
    {
        $city = trim((string) config('services.econt.sender.city'));
        $postCode = trim((string) config('services.econt.sender.postcode'));
        $street = trim((string) config('services.econt.sender.street'));

        if ($city === '' || $postCode === '' || $street === '') {
            throw new RuntimeException('Липсва конфигурация за адреса за връщане към търговеца в Еконт.');
        }

        $address = [
            'city' => [
                'country' => [
                    'code3' => 'BGR',
                ],
                'name' => $city,
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

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone) ?? '';

        if ($phone === '') {
            throw new RuntimeException('Невалиден телефонен номер за обратната пратка.');
        }

        if (! str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+359'.substr($phone, 1);
            } else {
                $phone = '+359'.$phone;
            }
        }

        return $phone;
    }
}
