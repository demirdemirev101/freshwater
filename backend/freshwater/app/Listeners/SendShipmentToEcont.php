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
    // Safeguard to prevent double sending of the same shipment to Econt, implemented in the handle method.
    use InteractsWithQueue;

    // Set the number of attempts for retrying the job in case of failure
    public $tries = 3;
    // Max timeout for the job execution to prevent hanging tasks
    public $timeout = 60;
    // Time between retries (in seconds) - can be customized based on the expected time for transient issues to resolve 
    // For example, if Econt API is down, we might want to wait a bit before retrying to avoid hitting it too frequently
    public $backoff = [30, 60, 120];

    /*
    * Handles the event when an order is ready for shipment and sends the shipment details to Econt.
    * - It first checks if the shipment is in 'created' status to ensure it's ready to be sent.
    * - It uses an atomic update to change the status to 'pending' to prevent multiple processes from sending the same shipment concurrently.
    * - If Econt integration is disabled (e.g., in local environments), it updates the shipment status to 'confirmed' without sending and logs this action.
    * - It prepares the payload using a mapper and sends it to Econt, then processes the response to update the shipment record accordingly.
    * - In case of errors, it updates the shipment status to 'error' and logs the error details, allowing for retries based on the defined attempts
    *    and backoff strategy.
    * - If the job ultimately fails after all retries, it marks the shipment as 'error' and dispatches a notification job for the administrator.
    * - It also dispatches a job to send a tracking email to the customer once the shipment is confirmed by Econt.
    * Atomic guard is implemented here to prevent double sending of the same shipment, which can happen if multiple events are fired for the same order
    *  or if the job is retried due to transient errors. By checking the status and using an atomic update, we ensure that only one process
    *  can send the shipment to Econt.
    */
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
    /**
     * Processes the response from Econt after sending the shipment details. It updates the shipment record with the carrier response,
     *  tracking number, label URL, and changes the status to 'confirmed'. It logs the successful confirmation and dispatches a job
     *  to send a tracking email to the customer.
     */
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

        Log::info('Shipment confirmed by Econt', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $label['shipmentNumber'],
            'label_url' => $label['pdfURL'] ?? null,
        ]);

        // Изпрати email на клиента с tracking number
        dispatch(new SendTrackingEmailJob($shipment->id));
    }

    /**
     * Handles errors that occur during the shipment creation process. It updates the shipment status to 'error' and logs the error details,
     *  including the attempt count. The error message is stored in the shipment record for later reference, and the method allows for retries 
     *  based on the defined attempts and backoff strategy in the job configuration. This centralized error handling ensures that 
     *  any issues during the Econt integration are properly logged and the shipment status is updated accordingly,
     *  allowing for monitoring and manual intervention if needed after multiple failed attempts.
     */
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
     * Handle job failure after all retry attempts have been exhausted.
     * It updates the shipment status to 'error' and logs the critical failure, including the error message.
     * It also dispatches a notification job to alert the administrator about the shipment failure, allowing for timely intervention to resolve the issue.
     *  This method ensures that even in cases of persistent failures, the system is aware of the problem and can take appropriate action.
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
