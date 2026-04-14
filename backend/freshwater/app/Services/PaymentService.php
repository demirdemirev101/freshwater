<?php

namespace App\Services;

use App\Models\Order;
use App\Events\OrderReadyForShipment;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Handle the payment processing for an order based on its payment method.
     *  The method uses a match expression to determine the appropriate handling logic for each payment method,
     *  such as cash on delivery or bank transfer. If the payment method is not recognized, an exception is thrown.
     */
    public function handle(Order $order): void
    {
        match ($order->payment_method) {
            'cod' => $this->handleCashOnDelivery($order),
            'bank_transfer' => $this->handleBankTransfer($order),
            default => throw new Exception('Неразпознат метод на плащане.'),
        };
    }
    /**
     * Handle the cash on delivery payment method for an order. 
     *  This method updates the order's payment status to 'unpaid' and its status to 'pending_review' within a database transaction to ensure data integrity.
     *  This indicates that the order is awaiting review before it can be processed for shipment.
     *  The use of a transaction ensures that the changes to the order are atomic, meaning that they will either all succeed or all fail together,
     *  preventing any inconsistent state in the database.
     */
    private function handleCashOnDelivery(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->updateQuietly([
                'payment_status' => 'unpaid',
                'status'         => 'pending_review',
            ]);
        });
    }
    /**
     * Handle the bank transfer payment method for an order.
     *  This method updates the order's payment status to 'pending' and its status to 'pending' within a database transaction.
     *  This indicates that the order is awaiting payment confirmation before it can be processed for shipment.
     * The use of a transaction ensures that the changes to the order are atomic, meaning that they will either all succeed or all fail together,
     * preventing any inconsistent state in the database.
     */
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
