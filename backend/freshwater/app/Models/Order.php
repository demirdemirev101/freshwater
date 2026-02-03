<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postcode',
        'shipping_country',
        'holiday_delivery_day',
        'status',
        'subtotal',
        'shipping_price',
        'total',
        'payment_method',
        'payment_status',
        'notes'
    ];

    protected $casts = [
        'holiday_delivery_day' => 'date',
        'order_confirmation_sent_at' => 'datetime',
        'admin_notification_sent_at' => 'datetime',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items() : HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public static function booted()
    {
        static::creating(function ($order) {
            if (is_null($order->user_id)) {
                $order->user_id = null;
            }
        });
    }
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
    public function canEdit(): bool
    {
        return Auth::user()?->can('edit orders') ?? false;
    }
}
