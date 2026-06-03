<?php

namespace App\Services\Shipment;

use App\Enums\OrderStatus;
use App\Events\ShipmentCreated;
use App\Mail\OrderReturnRequestedMail;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ShipmentReturnService
{
    public function __construct(
        private ShipmentMeasurementService $shipmentMeasurementService
    ) {}

    public function createReturnLabel(Order $order): Shipment
    {
        $order->loadMissing(['shipment', 'returnShipment', 'items.product']);

        if (! $order->shipment) {
            throw new RuntimeException('Поръчката няма изходяща пратка за заявка за връщане.');
        }

        if (empty($order->shipment->carrier_shipment_id)) {
            throw new RuntimeException('Изходящата пратка още няма номер в Еконт.');
        }

        if ($order->returnShipment) {
            throw new RuntimeException('За тази поръчка вече има създадена обратна пратка.');
        }

        $returnShipment = DB::transaction(function () use ($order): Shipment {
            $lockedOrder = Order::with(['shipment', 'returnShipment', 'items.product'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->returnShipment) {
                throw new RuntimeException('За тази поръчка вече има създадена обратна пратка.');
            }

            $outboundShipment = $lockedOrder->shipment;

            if (! $outboundShipment) {
                throw new RuntimeException('Липсва изходяща пратка за заявката за връщане.');
            }

            $shipment = $this->shipmentMeasurementService->applyToShipment(
                $lockedOrder->shipments()->make([
                    'carrier' => 'econt',
                    'direction' => 'return',
                    'pack_count' => $outboundShipment->pack_count ?: 1,
                    'declared_value' => $outboundShipment->declared_value ?: ($lockedOrder->subtotal ?? 0),
                    'cash_on_delivery' => 0,
                    'shipping_price_estimated' => $outboundShipment->shipping_price_real ?: $outboundShipment->shipping_price_estimated,
                    'delivery_type' => $outboundShipment->delivery_type,
                    'office_code' => $outboundShipment->office_code,
                    'status' => 'created',
                ]),
                $lockedOrder,
            );

            $shipment->save();

            $lockedOrder->updateQuietly([
                'status' => OrderStatus::RETURN_REQUESTED->value,
            ]);

            return $shipment;
        });

        event(new ShipmentCreated($order->id, $returnShipment->id));

        if ($order->customer_email) {
            Mail::to($order->customer_email)->send(new OrderReturnRequestedMail($order->id));
        }

        Log::info('Return shipment requested', [
            'order_id' => $order->id,
            'shipment_id' => $returnShipment->id,
        ]);

        return $returnShipment->fresh();
    }
}
