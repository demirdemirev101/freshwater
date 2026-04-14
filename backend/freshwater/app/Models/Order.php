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
    /**
     * Use the HasFactory trait to enable factory methods for the Order model.
     */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * Cast specific attributes to appropriate data types for consistent handling in the application.
     */
    protected $casts = [
        'holiday_delivery_day' => 'date',
        'order_confirmation_sent_at' => 'datetime',
        'admin_notification_sent_at' => 'datetime',
    ];

    /**
     * Define a belongs-to relationship to the User model, indicating that each Order is associated with a single User.
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Define a has-many relationship to the OrderItem model, indicating that each Order can have multiple associated OrderItems.
     */
    public function items() : HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    /**
     * Boot method to handle model events. When creating an order, set user_id to null if not provided,
     * indicating a guest checkout. Otherwise it will automatically link the order to the user_id if provided,
     * indicating a registered user checkout.
     */
    public static function booted()
    {
        static::creating(function ($order) {
            if (is_null($order->user_id)) {
                $order->user_id = null;
            }
        });
    }
    /**
     * Define a has-one relationship to the Shipment model, indicating that each Order can have a single associated Shipment.
     */
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
}
