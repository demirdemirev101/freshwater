<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'carrier',

        'carrier_shipment_id',
        'return_carrier_shipment_id',
        'tracking_number',
        'return_tracking_number',
        'label_url',
        'return_label_url',

        'weight',
        'height',
        'width',
        'length',
        'pack_count',
        'shipment_type',
        'delivery_type',
        'office_code',

        'declared_value',
        'cash_on_delivery',
        'shipping_price_estimated',
        'shipping_price_real',

        'carrier_payload',
        'return_carrier_payload',
        'carrier_response',
        'return_carrier_response',

        'status',
        'return_status',
        'sent_to_carrier_at',
        'return_sent_to_carrier_at',
        'error_message',
        'return_error_message',
    ];

    /**
     * Cast specific attributes to appropriate data types for consistent handling in the application.
     */
    protected $casts = [
        'carrier_payload' => 'array',
        'return_carrier_payload' => 'array',
        'carrier_response' => 'array',
        'return_carrier_response' => 'array',

        'weight' => 'decimal:3',
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'cash_on_delivery' => 'decimal:2',
        'shipping_price_estimated' => 'decimal:2',
        'shipping_price_real' => 'decimal:2',

        'sent_to_carrier_at' => 'datetime',
        'return_sent_to_carrier_at' => 'datetime',
    ];

    /**
     * Define a belongs-to relationship to the Order model, indicating that each Shipment is associated with a single Order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
