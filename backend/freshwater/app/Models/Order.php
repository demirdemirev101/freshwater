<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postcode',
        'shipping_country',
        'status',
        'subtotal',
        'shipping_price',
        'total',
        'payment_method',
        'payment_status',
        'notes'
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items() : HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function recalculateTotal() : void
    {
        $this->subtotal = $this->items()->sum('total');
        $this->total = $this->subtotal + ($this->shipping_price ?? 0);
        $this->saveQuietly();
        $this->refresh();
    }
    public static function booted()
    {
        static::creating(function ($order) {
            if (is_null($order->user_id)) {
                $order->user_id = null;
            }
        });

        static::deleting(function ($order) {
            $order->items()->delete();
        });
    }
}
