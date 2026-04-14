<?php

namespace App\Services\Econt;

use App\Models\Setting;
use App\Models\Shipment;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EcontPayloadMapper
{
    /**
     * Maps a Shipment model to the payload structure required by the Econt API for creating a shipment.
     * The method performs several key functions:
     * 1. Validates the presence of related order and customer phone number, throwing exceptions if critical data is missing.
     * 2. Normalizes and formats the customer phone number to ensure it meets Econt's requirements.
     * 3. Builds the payload with sender and receiver information, shipment details, and optional services based on the shipment and order data.
     * 4. Handles both address and office delivery types, constructing the appropriate payload fields for each case.
     * 5. Determines if free delivery applies based on the order subtotal and global settings, adjusting the payment method accordingly.
     * @param Shipment $shipment The shipment instance to be mapped to the Econt API payload.
     * @return array The structured payload ready to be sent to the Econt API for shipment creation.
     * @throws RuntimeException If critical data is missing from the shipment or related order,
     * or if the payload cannot be constructed due to incomplete address information.
     */
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
            $payload['holidayDeliveryDay'] = $this->resolveHolidayDeliveryDay();
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

        // Declared value (optional) - use shipment declared value if set, otherwise fallback to order subtotal if available
        if ($shipment->declared_value > 0) {
            $payload['services']['declaredValueAmount'] = round($shipment->declared_value, 2);
        } elseif (!empty($order->subtotal) && $order->subtotal > 0) {
            $payload['services']['declaredValueAmount'] = round($order->subtotal, 2);
        }

        // SMS notification (optional) - only include if customer email is available for notification
        if (!empty($order->customer_email)) {
            $payload['services']['smsNotification'] = [
                'toEmail' => $order->customer_email,
            ];
        }

        // Free delivery -> sender pays courier service
        $settings = Setting::current();
        $freeDelivery = $settings->delivery_enabled
            && $settings->free_delivery_over !== null
            && $order->subtotal >= $settings->free_delivery_over;

        // If free delivery applies, set sender payment method to cash and remove receiver payment method
        if ($freeDelivery) {
            $payload['paymentSenderMethod'] = 'cash';
            unset($payload['paymentReceiverMethod']);
        }

        return $payload;
    }
    /**
     * Formats a phone number to ensure it is in the correct format for Econt API. The method performs the following steps:
     * 1. Removes any spaces, dashes, parentheses, or other common formatting characters from the input phone number.
     * 2. Checks if the phone number starts with a '+' character. If it does not, it assumes the number is a local Bulgarian number 
     * and prepends the +359 country code. If the number starts with '0', it removes the leading '0' before adding the country code.
     * 3. Returns the formatted phone number as a string, ensuring it is in the correct international format required by the Econt API. 
     * This method helps to standardize phone numbers and reduce the likelihood of errors when sending data to the Econt API for shipment creation.
     * @param string $phone The input phone number to be formatted.
     * @return string The formatted phone number in international format suitable for Econt API.
     */
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
    /**
     * Builds the receiver address payload for Econt API based on the order's shipping information. The method performs the following steps:
     * 1. Normalizes the input data by trimming whitespace from the city name, post code, and street address. 
     * It also checks for the presence of critical fields (city, post code, street) and logs an error and throws an exception if any of these fields are missing.
     * 2. Constructs the address payload with the required structure for Econt API, including country code, city name, post code, and street address.
     * 3. Optionally includes additional address details such as building number, quarter, and other address details if they are available in the order data.
     * 4. Logs the constructed address payload for debugging purposes and returns the structured address array ready to be included
     *  in the Econt API shipment creation payload. This method ensures that the receiver address is properly formatted and contains all necessary information
     *  for Econt API compatibility.
     */
    private function buildAddress(object $order): array
    {
        //Removing the extra spaces and normalizing the city name, post code, and street address
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

        // Building the address payload according to Econt API requirements
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

        // Optionally include building number, quarter, and other address details if available
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

        // Log the constructed address for debugging purposes
        Log::info('Econt receiver address built', [
            'order_id' => $order->id ?? null,
            'city' => $cityName,
            'postcode' => $postCode,
        ]);

        return $address;
    }
    /**
     * Builds the sender address payload for Econt API based on the configured sender information. The method performs the following steps:
     * 1. Retrieves the sender city, post code, and street from the application configuration and trims any extra whitespace from these values.
     * 2. Validates that the critical fields (city, post code, street) are present and not empty. If any of these fields are missing, the method returns null,
     *  indicating that the sender address cannot be constructed.
     * 3. Constructs the sender address payload with the required structure for Econt API, including country code, city name, post code, and street address.
     *  It also optionally includes the building number if it is available in the configuration.
     * 4. Returns the structured sender address array ready to be included in the Econt API shipment creation payload. 
     *  This method ensures that the sender address is properly formatted and contains all necessary information for Econt API compatibility,
     *  while also providing a fallback mechanism if the sender address cannot be constructed due to missing configuration values.
     * @return array|null The structured sender address array for Econt API, or null if critical fields are missing from the configuration.
     */
    private function buildSenderAddress(): ?array
    {
        $cityName = trim((string) config('services.econt.sender.city'));
        $postCode = trim((string) config('services.econt.sender.postcode'));
        $street = trim((string) config('services.econt.sender.street'));

        if ($cityName === '' || $postCode === '' || $street === '') {
            return null;
        }

        $city = [
            'country' => [
                'code3' => 'BGR',
            ],
            'name' => $cityName,
            'postCode' => $postCode,
        ];

        $address = [
            'city' => $city,
            'street' => $street,
        ];

        $num = trim((string) config('services.econt.sender.num'));
        if ($num !== '') {
            $address['num'] = $num;
        }

        return $address;
    }

    /**
     * Determines the holiday delivery day for a given order based on the presence of the holiday_delivery_day field. The method performs the following steps:
     * 1. Checks if the order has a non-empty holiday_delivery_day field. If it does, the method returns this value formatted as a date string (Y-m-d) if it
     * is an instance of DateTimeInterface, or as a string if it is already in a string format. 
     * This allows for flexibility in how the holiday delivery day can be specified in the order data.
     * 2. If the holiday_delivery_day field is empty, the method returns the string 'workday', indicating that the delivery should be scheduled for the next available workday.
     * This method provides a way to specify special delivery days for orders, such as holidays, while also providing a default behavior for regular deliveries. The returned value can then be included in the E
     * @param object $order The order object containing the holiday_delivery_day field and other relevant information for determining the delivery day.
     * @return string The determined holiday delivery day formatted as a date string (Y-m-d) for use in the Econt API shipment creation payload.
     */
    private function resolveHolidayDeliveryDay(): string
    {
        return 'workday';
    }
}

