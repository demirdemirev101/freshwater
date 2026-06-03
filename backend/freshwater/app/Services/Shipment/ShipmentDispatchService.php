<?php

namespace App\Services\Shipment;

use App\Jobs\NotifyAdminShipmentFailedJob;
use App\Jobs\SendTrackingEmailJob;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Econt\EcontPayloadMapper;
use App\Services\Econt\EcontService;
use App\Support\ErrorMessages;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ShipmentDispatchService
{
    public function __construct(
        private EcontService $econtService,
        private EcontPayloadMapper $payloadMapper
    ) {}

    public function dispatchForOrder(int $orderId, int $attempts, int $maxAttempts): void
    {
        $order = Order::with('shipment')->findOrFail($orderId);
        $shipment = $order->shipment;

        if (! $shipment || $shipment->status !== 'created') {
            Log::warning('Shipment not ready for Econt', [
                'order_id' => $order->id,
                'status' => $shipment?->status,
            ]);

            return;
        }

        if (! config('services.econt.enabled')) {
            $this->markLocalShipmentAsConfirmed($shipment);

            return;
        }

        $updated = Shipment::where('id', $shipment->id)
            ->where('status', 'created')
            ->update(['status' => 'pending']);

        if (! $updated) {
            Log::info('Shipment already being processed', [
                'shipment_id' => $shipment->id,
            ]);

            return;
        }

        $shipment->refresh();

        try {
            $payload = $this->payloadMapper->map($shipment);

            $shipment->update([
                'carrier_payload' => $payload,
            ]);

            Log::info('Sending shipment to Econt', [
                'shipment_id' => $shipment->id,
                'summary' => [
                    'delivery_type' => $shipment->delivery_type,
                    'pack_count' => $shipment->pack_count,
                    'weight' => $shipment->weight,
                    'has_cod' => ($shipment->cash_on_delivery ?? 0) > 0,
                ],
            ]);

            $response = $this->econtService->createLabel($payload);

            Log::info('Econt response received', [
                'shipment_id' => $shipment->id,
                'tracking_number' => data_get($response, 'label.shipmentNumber'),
                'total_price' => data_get($response, 'label.totalPrice'),
            ]);

            $this->confirmShipment($shipment, $response);
        } catch (Throwable $e) {
            $this->handleDispatchError($shipment, $e, $attempts, $maxAttempts);

            throw $e;
        }
    }

    public function markDispatchFailed(int $orderId, Throwable $exception): void
    {
        $order = Order::with('shipment')->find($orderId);

        if (! $order || ! $order->shipment) {
            return;
        }

        $order->shipment->update([
            'status' => 'error',
            'error_message' => ErrorMessages::SHIPMENT_CREATE_FAILED_AFTER_RETRIES.' '.$exception->getMessage(),
        ]);

        Log::critical('Econt shipment job failed permanently', [
            'order_id' => $order->id,
            'shipment_id' => $order->shipment->id,
            'error' => $exception->getMessage(),
        ]);

        dispatch(new NotifyAdminShipmentFailedJob($order->shipment->id));
    }

    private function markLocalShipmentAsConfirmed(Shipment $shipment): void
    {
        $shipment->update([
            'status' => 'confirmed',
            'tracking_number' => 'TEST-'.$shipment->id,
            'carrier_response' => [
                'message' => 'Еконт е изключен в локалната среда.',
            ],
            'error_message' => null,
        ]);

        Log::info('Econt skipped (disabled)', [
            'shipment_id' => $shipment->id,
        ]);

        dispatch(new SendTrackingEmailJob($shipment->id));
    }

    private function confirmShipment(Shipment $shipment, array $response): void
    {
        $label = $response['label'] ?? null;

        if (! $label || empty($label['shipmentNumber'])) {
            throw new RuntimeException('Невалиден отговор от Еконт: липсва shipmentNumber.');
        }

        $shipment->update([
            'carrier_response' => $response,
            'carrier_shipment_id' => $label['shipmentNumber'],
            'tracking_number' => $label['shipmentNumber'],
            'label_url' => $label['pdfURL'] ?? null,
            'shipping_price_real' => $label['totalPrice'] ?? null,
            'status' => 'confirmed',
            'sent_to_carrier_at' => now(),
            'error_message' => null,
        ]);

        Log::info('Shipment confirmed by Econt', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $label['shipmentNumber'],
            'label_url' => $label['pdfURL'] ?? null,
        ]);

        dispatch(new SendTrackingEmailJob($shipment->id));
    }

    private function handleDispatchError(Shipment $shipment, Throwable $exception, int $attempts, int $maxAttempts): void
    {
        $errorMessage = $exception->getMessage();

        Log::error('Econt shipment creation failed', [
            'shipment_id' => $shipment->id,
            'error' => $errorMessage,
            'attempt' => $attempts,
        ]);

        $status = $attempts >= $maxAttempts ? 'error' : 'pending';

        $shipment->update([
            'status' => $status,
            'error_message' => ErrorMessages::SHIPMENT_CREATE_FAILED.' '.$errorMessage,
        ]);
    }
}
