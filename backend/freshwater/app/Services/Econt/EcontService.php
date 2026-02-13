<?php

namespace App\Services\Econt;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * This service handles all interactions with the Econt API, including creating shipment labels, tracking shipments, fetching cities and offices,
 *  calculating shipping prices and deleting labels.
 *  It includes robust error handling and logging to ensure that any issues with the API are properly recorded and can be diagnosed.
 *  The service also implements safeguards against oversized API responses to protect queue workers from memory issues.
 */
class EcontService
{
    // Define maximum JSON body size to prevent memory issues in queue workers
    private const MAX_JSON_BODY_BYTES = 16777216;
    // Define a smaller size for logging previews to avoid logging excessively large payloads
    private const LOG_BODY_PREVIEW_BYTES = 1024;

    private string $baseUrl;
    private string $username;
    private string $password;

    // Initialize the service with configuration values for the Econt API
    public function __construct()
    {
        $this->baseUrl = config('services.econt.base_url');
        $this->username = config('services.econt.username');
        $this->password = config('services.econt.password');
    }

    /**
     * Create a shipment label with the Econt API. The method accepts a payload array containing all necessary information for the label creation,
     *  and a mode which can be 'create', 'calculate' or 'validate'.
     *  It sends a POST request to the Econt API and handles the response, 
     *  including error logging and exception throwing for any API errors or invalid responses.
     */
    public function createLabel(array $payload, string $mode = 'create'): array
    {
        // Extract the receiver city from the payload for logging purposes, as it can be useful for diagnosing issues related to specific locations.
        $receiverCity = $payload['receiverAddress']['city'] ?? null;

        Log::info('FINAL ECONT PAYLOAD', [
            'city' => $receiverCity,
        ]);
        
        // Send the request to the Econt API with appropriate options and authentication.
        $response = Http::withOptions([
                'verify' => config('services.econt.verify_ssl'),
            ])
            ->timeout(30)
            ->withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/Shipments/LabelService.createLabel.json", [
                'label' => $payload,
                'mode' => $mode, // create, calculate или validate
            ]);

        // Decode the JSON response with safeguards against oversized payloads.
        $json = $this->decodeJsonResponse($response, 'createLabel');

        // Check for HTTP errors or API-level errors in the response and log detailed information for troubleshooting.
        if ($response->failed()) {

            Log::error('Econt createLabel failed', [
                'status' => $response->status(),
                'body_preview' => $this->getResponseBodyPreview($response),
                'json' => $json,
                'mode' => $mode,
                'city' => $receiverCity,
            ]);
            // Attempt to extract a meaningful error message from the API response, which may be nested in various fields,
            // to provide better context in the exception.
            $errorMessage = $this->extractErrorMessage($json['error'] ?? $json) ?? 'Unknown error';

            throw new RuntimeException('Econt API error: ' . $errorMessage);
        }

        // Even if the HTTP response is successful, the Econt API might return an error in the JSON body, so we check for that as well.
        if (isset($json['error'])) {
            $errorMessage = $this->extractErrorMessage($json['error']) ?? 'Unknown error';

            throw new RuntimeException('Econt error: ' . $errorMessage);
        }

        return $json;
    }

    /**
     * Track a shipment using its shipment number. This method sends a request to the Econt API to retrieve the current status of the shipment,
     *  and includes error handling and logging similar to the createLabel method to ensure that any issues with
     *  tracking are properly recorded and can be diagnosed. The response is decoded with safeguards against oversized payload
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

        return $this->decodeJsonResponse($response, 'trackShipment');
    }
    /**
     * Extracts an error message from nested error data.
     */
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
     * Decode JSON responses with a hard cap to protect queue workers from oversized payloads.
     * If the response body exceeds the defined maximum size, an exception is thrown to prevent memory issues. 
     * This method also ensures that the decoded JSON is an array and throws an exception if the JSON is invalid or not in the expected format.
     */
    private function decodeJsonResponse(Response $response, string $context): array
    {
        [$body, $truncated] = $this->readResponseBody($response, self::MAX_JSON_BODY_BYTES);

        if ($truncated) {
            throw new RuntimeException("Econt {$context} response exceeded " . self::MAX_JSON_BODY_BYTES . ' bytes.');
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Econt {$context} returned invalid JSON.");
        }

        return $decoded;
    }

    /**
     * Get a preview of the response body for logging purposes, with a defined maximum size to avoid logging excessively large payloads.
     */
    private function getResponseBodyPreview(Response $response): string
    {
        [$body, $truncated] = $this->readResponseBody($response, self::LOG_BODY_PREVIEW_BYTES);

        return $truncated ? $body . '...[truncated]' : $body;
    }

    /**
     * @return array{0: string, 1: bool}
     * Reads the response body up to a specified maximum number of bytes. Returns the body content and a boolean indicating if it was truncated.
     * This method ensures that we do not read more than the allowed number of bytes,
     *  which helps protect against memory issues in queue workers when dealing with large API responses.
     */
    private function readResponseBody(Response $response, int $maxBytes): array
    {
        // Use the PSR-7 response body stream to read the content with a hard cap on the number of bytes.
        $stream = $response->toPsrResponse()->getBody();

        // Ensure we start reading from the beginning of the stream if it is seekable.
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        // Read up to maxBytes + 1 to determine if the body exceeds the limit.
        $body = $stream->read($maxBytes + 1);
        // Check if the body was truncated by comparing the length of the read content to the maximum allowed bytes.
        $truncated = strlen($body) > $maxBytes;

        // If the body was truncated, we only keep the allowed number of bytes to prevent memory issues.
        if ($truncated) {
            $body = substr($body, 0, $maxBytes);
        }

        return [$body, $truncated];
    }

    /**
     * Download the shipment label PDF URL for a given shipment number. This method sends a request to the Econt API to retrieve the label information
     *  and returns the URL of the PDF label if available. It includes error handling to ensure that any issues with the API request are properly logged
     *  and do not cause unhandled exceptions in the application. If the API call fails or the expected data is not present in the response,
     *  the method returns null.
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
            $data = $this->decodeJsonResponse($response, 'downloadLabel');
            return $data['pdfURL'] ?? null;
        }

        return null;
    }
    /**
     * Delete shipment labels for the given shipment numbers. This method sends a request to the Econt API to delete the specified labels
     *  and handles the response, including error logging and exception throwing for any API errors or invalid responses. 
     * The method returns the API response as an array if the deletion is successful, or throws an exception if there are errors.
     */
    public function deleteLabels(array $shipmentNumbers): array
    {
        $response = Http::withOptions([
                'verify' => config('services.econt.verify_ssl'),
            ])
            ->withBasicAuth($this->username, $this->password)
            ->post("{$this->baseUrl}/Shipments/LabelService.deleteLabels.json", [
                'shipmentNumbers' => $shipmentNumbers,
            ]);

        $json = $this->decodeJsonResponse($response, 'deleteLabels');

        if ($response->failed()) {
            Log::error('Econt deleteLabels failed', [
                'status' => $response->status(),
                'body_preview' => $this->getResponseBodyPreview($response),
                'json' => $json,
                'shipment_numbers' => $shipmentNumbers,
            ]);

            $errorMessage = $this->extractErrorMessage($json['error'] ?? $json) ?? 'Unknown error';

            throw new RuntimeException('Econt API error: ' . $errorMessage);
        }

        if (isset($json['error'])) {
            $errorMessage = $this->extractErrorMessage($json['error']) ?? 'Unknown error';

            throw new RuntimeException('Econt error: ' . $errorMessage);
        }

        return $json;
    }
    /**
     * Fetch a list of cities from the Econt API, optionally filtered by a search term. 
     * This method sends a request to the Econt API to retrieve the list of cities
     *  and returns an array of cities that match the search criteria. 
     * It includes error handling to ensure that any issues with the API request are properly logged
     *  and do not cause unhandled exceptions in the application.
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
            $data = $this->decodeJsonResponse($response, 'getCities');
            return is_array($data['cities'] ?? null) ? $data['cities'] : [];
        }

        return [];
    }

    /**
     * Fetch a list of offices for a given city ID from the Econt API. This method sends a request to the Econt API to retrieve the list of offices
     *  associated with the specified city ID and returns an array of offices.
     * It includes error handling to ensure that any issues with the API request are properly logged
     * and do not cause unhandled exceptions in the application. If the API call fails or the expected data is not present in the response,
     * the method returns an empty array.
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
            $data = $this->decodeJsonResponse($response, 'getOffices');
            return is_array($data['offices'] ?? null) ? $data['offices'] : [];
        }

        return [];
    }

    /**
     * Calculate the shipping price for a given payload. This method sends a request to the Econt API to calculate the price based on the provided payload,
     *  and returns the calculated price if available. It includes error handling to ensure that any issues with the API request are properly logged
     *  and do not cause unhandled exceptions in the application. If the API call fails or the expected data is not present in the response, it returns null.
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
            $data = $this->decodeJsonResponse($response, 'calculatePrice');
            return $data['label']['totalPrice'] ?? null;
        } else {
            // Логване на грешка
            Log::error('Econt price calculation failed', [
                'status' => $response->status(),
                'body_preview' => $this->getResponseBodyPreview($response),
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
