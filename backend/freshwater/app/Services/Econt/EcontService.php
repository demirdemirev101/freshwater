<?php

namespace App\Services\Econt;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EcontService
{
    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->baseUrl = config('services.econt.base_url');
        $this->username = config('services.econt.username');
        $this->password = config('services.econt.password');
    }

    /**
     * Създаване на товарителница в Еконт
     */
    public function createLabel(array $payload, string $mode = 'create'): array
    {
        $receiverCity = $payload['receiverAddress']['city'] ?? null;

        Log::info('FINAL ECONT PAYLOAD', [
            'city' => $receiverCity,
        ]);
        
        $response = Http::withOptions([
                'verify' => config('services.econt.verify_ssl'),
            ])
            ->timeout(30)
            ->withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/Shipments/LabelService.createLabel.json", [
                'label' => $payload,
                'mode' => $mode, // create, calculate или validate
            ]);

        if ($response->failed()) {
            $json = $response->json();

            Log::error('Econt createLabel failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $json,
                'payload' => $payload,
            ]);

            $errorMessage = $this->extractErrorMessage($json['error'] ?? $json) ?? 'Unknown error';

            throw new RuntimeException('Econt API error: ' . $errorMessage);
        }

        $data = $response->json();

        if (isset($data['error'])) {
            $errorMessage = $this->extractErrorMessage($data['error']) ?? 'Unknown error';

            throw new RuntimeException('Econt error: ' . $errorMessage);
        }

        return $data;
    }

    /**
     * Проследяване на пратка
     */
    public function trackShipment(string $shipmentNumber): array
    {
        $response = Http::withOptions([
                'verify' => config('services.econt.verify_ssl'),
            ])
            ->withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/Shipments/ShipmentService.getShipmentStatuses.json", [
                'shipmentNumbers' => [$shipmentNumber],
            ]);

        if ($response->failed()) {
            Log::error('Econt tracking failed', [
                'shipment_number' => $shipmentNumber,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Econt tracking error');
        }

        return $response->json();
    }

    private function extractErrorMessage(?array $data): ?string
    {
        if (!$data) {
            return null;
        }

        $directMessage = $data['message']
            ?? $data['messageBg']
            ?? $data['errorMessage']
            ?? $data['errorMessageBg']
            ?? null;

        if (is_string($directMessage) && trim($directMessage) !== '') {
            return $directMessage;
        }

        foreach ($data['innerErrors'] ?? [] as $innerError) {
            $innerMessage = $this->extractErrorMessage($innerError);
            if ($innerMessage) {
                return $innerMessage;
            }
        }

        return null;
    }

    /**
     * Изтегляне на PDF етикет
     */
    public function downloadLabel(string $shipmentNumber): ?string
    {
        $response = Http::withOptions([
                'verify' => config('services.econt.verify_ssl'),
            ])
            ->withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/Shipments/LabelService.printLabels.json", [
                'shipmentNumbers' => [$shipmentNumber],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['pdfURL'] ?? null;
        }

        return null;
    }

    /**
     * Вземане на градове от Еконт
     */
    public function getCities(string $search = ''): array
    {
        $response = Http::withOptions([
                'verify' => config('services.econt.verify_ssl'),
            ])
            ->withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/Nomenclatures/NomenclaturesService.getCities.json", [
                'countryCode' => 'BGR',
                'name' => $search,
            ]);

        if ($response->successful()) {
            return $response->json('cities') ?? [];
        }

        return [];
    }

    /**
     * Вземане на офиси в град
     */
    public function getOffices(int $cityId): array
    {
        $response = Http::withOptions([
                'verify' => config('services.econt.verify_ssl'),
            ])
            ->withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/Nomenclatures/NomenclaturesService.getOffices.json", [
                'countryCode' => 'BGR',
                'cityID' => $cityId,
            ]);

        if ($response->successful()) {
            return $response->json('offices') ?? [];
        }

        return [];
    }

    /**
     * Калкулиране на цена
     */
    public function calculatePrice(array $payload): ?float
{
    try {
        $response = Http::withOptions([
            'verify' => config('services.econt.verify_ssl'),
        ])
        ->withBasicAuth($this->username, $this->password)
        ->post("{$this->baseUrl}/Shipments/LabelService.createLabel.json", [
            'label' => $payload,
            'mode' => 'calculate',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['label']['totalPrice'] ?? null;
        } else {
            // Логване на грешка
            Log::error('Econt price calculation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Econt price calculation exception', [
            'error' => $e->getMessage(),
        ]);
    }

    return null;
}
}
