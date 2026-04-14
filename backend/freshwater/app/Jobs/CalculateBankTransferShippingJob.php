<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Setting;
use App\Services\SettingsService;
use App\Support\ErrorMessages;
use App\Jobs\NotifyAdminBankTransferShippingFailedJob;
use RuntimeException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CalculateBankTransferShippingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Define the number of attempts, timeout, and backoff strategy for the job.
    public $tries = 3;
    // Max timeout for the job execution to prevent hanging tasks
    public $timeout = 60;
    // Time between retries (in seconds) - can be customized based on the expected time for transient issues to resolve
    public $backoff = [30, 120, 300];

    public function __construct(
        public int $orderId
    ) {}
    /**
     * Calculates the shipping costs for an order paid via bank transfer. It retrieves the order and its items, calculates the subtotal
     *  and checks if free delivery applies based on the settings.
     * If free delivery does not apply and Econt integration is enabled, it calculates the shipping price using the SettingsService.
     * If the shipping price calculation fails (e.g., returns a non-positive value), it throws a RuntimeException, which triggers the job's retry mechanism.
     *  If all retries are exhausted, the failed method is called to handle the failure case. If the shipping cost is successfully calculated,
     *  it updates the order's total and shipping price, and sends an order confirmation email to the customer if the email is available
     *  and the confirmation has not been sent yet.
     */
    public function handle(SettingsService $settingsService): void
    {
        $order = Order::with('items')->find($this->orderId);

        if (! $order || ! in_array($order->payment_method, ['bank_transfer', 'cod'], true)) {
            return;
        }

        $order->subtotal = $order->items()->sum('total');

        $settings = Setting::current();
        $freeDelivery = $settings->delivery_enabled
            && $settings->free_delivery_over !== null
            && $order->subtotal >= $settings->free_delivery_over;

        if ($freeDelivery || ! config('services.econt.enabled')) {
            $order->shipping_price = 0;
        } else {
            $order->shipping_price = $settingsService->calculateEcontShippingForOrder($order);
        }

        // If shipping price calculation fails, throw an exception to trigger the retry mechanism
        if (! $freeDelivery && config('services.econt.enabled') && $order->shipping_price <= 0) {
            throw new RuntimeException(ErrorMessages::BANK_TRANSFER_SHIPPING_FAILED);
        }

        $order->total = ($order->subtotal ?? 0) + ($order->shipping_price ?? 0);
        $order->saveQuietly();

        if (! empty($order->customer_email)) {
            $updated = Order::where('id', $order->id)
                ->whereNull('order_confirmation_sent_at')
                ->update([
                    'order_confirmation_sent_at' => now(),
                ]);

            if ($updated) {
                Mail::to($order->customer_email)
                    ->send(new OrderConfirmationMail($order->id));
            }
        }
    }

    /**
     * Handles the failure case when all retry attempts have been exhausted. It updates the order's payment status to 'failed' and dispatches a notification job
     * to alert the administrator about the failure, including the error message for further investigation and resolution. 
     * This ensures that critical issues with shipping cost calculation for bank transfer orders are promptly communicated to the admin for timely action.
     */
    public function failed(\Throwable $exception): void
    {
        $order = Order::find($this->orderId);

        if ($order) {
            $order->updateQuietly([
                'payment_status' => 'failed',
            ]);
        }

        dispatch(new NotifyAdminBankTransferShippingFailedJob($this->orderId, $exception->getMessage()));
    }
}
