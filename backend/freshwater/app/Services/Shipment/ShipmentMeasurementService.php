<?php

namespace App\Services\Shipment;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\WeightCalculatorService;
use Illuminate\Support\Facades\Schema;

class ShipmentMeasurementService
{
    private const MAX_PACKAGE_WEIGHT_KG = 30.0;

    private const MAX_PACKAGE_DIMENSION_CM = 60.0;

    private ?array $shipmentColumns = null;

    public function __construct(
        private WeightCalculatorService $weightCalculator
    ) {}

    public function forOrder(Order $order): array
    {
        $order->loadMissing('items.product');

        $weight = $this->weightCalculator->forOrder($order);
        $dimensions = $this->calculateDimensions($order);

        return [
            'weight' => $weight,
            'height' => $dimensions['height'],
            'width' => $dimensions['width'],
            'length' => $dimensions['length'],
            'shipment_type' => $this->resolveShipmentType($weight, $dimensions),
        ];
    }

    public function applyToShipment(Shipment $shipment, Order $order): Shipment
    {
        $measurements = $this->forOrder($order);

        $shipment->weight = $measurements['weight'];

        if ($this->shipmentTableHasColumn('height')) {
            $shipment->height = $measurements['height'];
        }

        if ($this->shipmentTableHasColumn('width')) {
            $shipment->width = $measurements['width'];
        }

        if ($this->shipmentTableHasColumn('length')) {
            $shipment->length = $measurements['length'];
        }

        if ($this->shipmentTableHasColumn('shipment_type')) {
            $shipment->shipment_type = $measurements['shipment_type'];
        }

        return $shipment;
    }

    private function shipmentTableHasColumn(string $column): bool
    {
        $this->shipmentColumns ??= Schema::getColumnListing('shipments');

        return in_array($column, $this->shipmentColumns, true);
    }

    private function calculateDimensions(Order $order): array
    {
        $height = 0.0;
        $width = null;
        $length = null;
        $hasHeight = false;
        $hasWidth = false;
        $hasLength = false;

        foreach ($order->items as $item) {
            $product = $item->product;
            $quantity = max(1, (int) $item->quantity);

            $productHeight = $this->normalizeDimension($product?->height);
            $productWidth = $this->normalizeDimension($product?->width);
            $productLength = $this->normalizeDimension($product?->length);

            if ($productHeight !== null) {
                $height += $productHeight * $quantity;
                $hasHeight = true;
            }

            if ($productWidth !== null) {
                $width = $width === null ? $productWidth : max($width, $productWidth);
                $hasWidth = true;
            }

            if ($productLength !== null) {
                $length = $length === null ? $productLength : max($length, $productLength);
                $hasLength = true;
            }
        }

        return [
            'height' => $hasHeight ? round($height, 2) : null,
            'width' => $hasWidth && $width !== null ? round($width, 2) : null,
            'length' => $hasLength && $length !== null ? round($length, 2) : null,
        ];
    }

    private function resolveShipmentType(float $weight, array $dimensions): string
    {
        if ($weight > self::MAX_PACKAGE_WEIGHT_KG) {
            return 'cargo';
        }

        foreach (['height', 'width', 'length'] as $key) {
            if (($dimensions[$key] ?? null) !== null && $dimensions[$key] > self::MAX_PACKAGE_DIMENSION_CM) {
                return 'cargo';
            }
        }

        return 'pack';
    }

    private function normalizeDimension(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = round((float) $value, 2);

        return $value > 0 ? $value : null;
    }
}
