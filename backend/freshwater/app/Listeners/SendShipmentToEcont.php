<?php

namespace App\Listeners;

use App\Jobs\NotifyAdminShipmentFailedJob;
use App\Jobs\SendTrackingEmailJob;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Econt\EcontPayloadMapper;
use App\Services\Econt\EcontService;
use App\Support\ErrorMessages;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendShipmentToEcont implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [30, 60, 120]; // секунди между retry-тата

    public function handle($event): void
    {
        $order = Order::with('shipment')->findOrFail($event->orderId);
        $shipment = $order->shipment;

        if (!$shipment || $shipment->status !== 'created') {
            Log::warning('Shipment not ready for Econt', [
                'order_id' => $order->id,
                'status' => $shipment?->status,
            ]);
            return;
        }

        /**
         * 🔒 FEATURE FLAG – DEV GUARD
         */
        if (!config('services.econt.enabled')) {
            $shipment->update([
                'status' => 'confirmed',
                'carrier_response' => [
                    'message' => 'Econt disabled (local environment)',
                ],
                'error_message' => null,
            ]);

            Log::info('Econt skipped (disabled)', [
                'shipment_id' => $shipment->id,
            ]);

            return;
        }
        
        // Atomic guard - предотвратява двойно изпращане
        $updated = Shipment::where('id', $shipment->id)
            ->where('status', 'created')
            ->update(['status' => 'pending']);

        if (!$updated) {
            Log::info('Shipment already being processed', [
                'shipment_id' => $shipment->id,
            ]);
            return;
        }

        $shipment->refresh();

        try {
            $econtService = app(EcontService::class);
            $mapper = app(EcontPayloadMapper::class);

            // Подготовка на payload
            $payload = $mapper->map($shipment);

            // Запис на payload-а преди изпращане
            $shipment->update([
                'carrier_payload' => $payload,
            ]);

            Log::info('Sending shipment to Econt', [
                'shipment_id' => $shipment->id,
                'payload' => $payload,
            ]);

            // Изпращане към Еконт
            $response = $econtService->createLabel($payload);

            Log::info('Econt response received', [
                'shipment_id' => $shipment->id,
                'response' => $response,
            ]);

            // Обработка на отговора
            $this->processResponse($shipment, $response);

        } catch (RuntimeException $e) {
            $this->handleError($shipment, $e);
            throw $e; // За да се активира retry механизма
        } catch (\Exception $e) {
            $this->handleError($shipment, $e);
            throw $e;
        }
    }

    private function processResponse($shipment, array $response): void
    {
        $label = $response['label'] ?? null;

        if (!$label || empty($label['shipmentNumber'])) {
            throw new RuntimeException('Invalid response from Econt: missing shipmentNumber');
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

        $shipment->loadMissing('order');
        if ($shipment->order && $shipment->order->status !== 'cancelled') {
            $shipment->order->update([
                'status' => 'shipped',
            ]);
        }

        Log::info('Shipment confirmed by Econt', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $label['shipmentNumber'],
            'label_url' => $label['pdfURL'] ?? null,
        ]);

        // Изпрати email на клиента с tracking number
        dispatch(new SendTrackingEmailJob($shipment->id));
    }

    private function handleError($shipment, \Exception $e): void
    {
        $errorMessage = $e->getMessage();

        Log::error('Econt shipment creation failed', [
            'shipment_id' => $shipment->id,
            'error' => $errorMessage,
            'attempt' => $this->attempts(),
        ]);

        $status = $this->attempts() >= $this->tries ? 'error' : 'pending';

        $shipment->update([
            'status' => $status,
            'error_message' => ErrorMessages::SHIPMENT_CREATE_FAILED . ' ' . $errorMessage,
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed($event, \Throwable $exception): void
    {
        $order = Order::with('shipment')->find($event->orderId);

        if ($order && $order->shipment) {
            $order->shipment->update([
                'status' => 'error',
                'error_message' => ErrorMessages::SHIPMENT_CREATE_FAILED_AFTER_RETRIES . ' ' . $exception->getMessage(),
            ]);

            Log::critical('Econt shipment job failed permanently', [
                'order_id' => $order->id,
                'shipment_id' => $order->shipment->id,
                'error' => $exception->getMessage(),
            ]);

            // Изпрати notification до администратор
            dispatch(new NotifyAdminShipmentFailedJob($order->shipment->id));
        }
    }
}
