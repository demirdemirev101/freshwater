<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderPricingService;
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

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [30, 120, 300];

    public function __construct(
        public int $orderId
    ) {}

    public function handle(OrderPricingService $orderPricingService): void
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
            $order->shipping_price = $orderPricingService->calculateEcontShippingForOrder($order);
        }

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
