<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order;

class Shipment extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'carrier',

        'carrier_shipment_id',
        'tracking_number',
        'label_url',

        'weight',
        'pack_count',
        'delivery_type',
        'office_code',

        'declared_value',
        'cash_on_delivery',
        'shipping_price_estimated',
        'shipping_price_real',

        'carrier_payload',
        'carrier_response',

        'status',
        'sent_to_carrier_at',
        'error_message',
    ];

    /**
     * Cast specific attributes to appropriate data types for consistent handling in the application.
     */

    protected $casts = [
        'carrier_payload' => 'array',
        'carrier_response' => 'array',

        'weight' => 'decimal:3',
        'declared_value' => 'decimal:2',
        'cash_on_delivery' => 'decimal:2',
        'shipping_price_estimated' => 'decimal:2',
        'shipping_price_real' => 'decimal:2',

        'sent_to_carrier_at' => 'datetime',
    ];

    /**
     * Define a belongs-to relationship to the Order model, indicating that each Shipment is associated with a single Order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}