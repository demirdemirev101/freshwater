<?php

namespace App\Listeners;

use App\Events\ShipmentCreated;
use App\Services\Shipment\ShipmentDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

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

    public function handle(ShipmentCreated $event): void
    {
        app(ShipmentDispatchService::class)->dispatchForOrder($event->orderId, $this->attempts(), $this->tries);
    }

    /**
     * Handle job failure after all retry attempts have been exhausted.
     * It updates the shipment status to 'error' and logs the critical failure, including the error message.
     * It also dispatches a notification job to alert the administrator about the shipment failure, allowing for timely intervention to resolve the issue.
     *  This method ensures that even in cases of persistent failures, the system is aware of the problem and can take appropriate action.
     */
    public function failed(ShipmentCreated $event, Throwable $exception): void
    {
        app(ShipmentDispatchService::class)->markDispatchFailed($event->orderId, $exception);
    }
}
