<?php

namespace App\Services;

use App\Models\Order;
use App\Events\OrderReadyForShipment;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function handle(Order $order): void
    {
        match ($order->payment_method) {
            'cod' => $this->handleCashOnDelivery($order),
            'bank_transfer' => $this->handleBankTransfer($order),
            default => throw new Exception('Неразпознат метод на плащане.'),
        };
    }

    private function handleCashOnDelivery(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->updateQuietly([
                'payment_status' => 'unpaid',
                'status'         => 'pending_review',
            ]);
        });
    }

    private function handleBankTransfer(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->updateQuietly([
                'payment_status' => 'pending',
                'status'         => 'pending',
            ]);
        });
    }
}
