<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'total',
    ];

    protected static function booted()
{
    // CREATE
    static::creating(function ($orderItem) {

        $product = Product::lockForUpdate()->findOrFail($orderItem->product_id);

        if ($orderItem->quantity !== null) {
            if ($product->quantity < $orderItem->quantity) {
                throw new \Exception('Продуктът няма наличност.');
            }
        }

        // snapshot
        $orderItem->product_name = $product->name;
        $orderItem->price = $product->price;
        $orderItem->total = $orderItem->quantity !== null
            ? $product->price * $orderItem->quantity
            : $product->price;
    });

    static::created(function ($orderItem) {

        if ($orderItem->quantity === null) {
            return;
        }

        $product = Product::lockForUpdate()->findOrFail($orderItem->product_id);
        $product->quantity -= $orderItem->quantity;
        $product->save();

        $orderItem->order?->recalculateTotal();
    });

    // UPDATE
    static::updating(function ($orderItem) {

        if (! $orderItem->isDirty('quantity')) {
            return;
        }

        $product = Product::lockForUpdate()->findOrFail($orderItem->product_id);

        $old = $orderItem->getOriginal('quantity');
        $new = $orderItem->quantity;

        // null → number
        if ($old === null && $new !== null) {
            if ($product->quantity < $new) {
                throw new \Exception('Продуктът няма наличност.');
            }
            $product->quantity -= $new;
        }

        // number → null
        if ($old !== null && $new === null) {
            $product->quantity += $old;
        }

        // number → number
        if ($old !== null && $new !== null) {
            $diff = $new - $old;
            if ($diff > 0 && $product->quantity < $diff) {
                throw new \Exception('Продуктът няма наличност.');
            }
            $product->quantity -= $diff;
        }

        $orderItem->total = $new !== null
            ? $orderItem->price * $new
            : $orderItem->price;

        $product->save();
    });

    static::updated(function ($orderItem) {
        $orderItem->order?->recalculateTotal();
    });

    // DELETE
    static::deleting(function ($orderItem) {
        if ($orderItem->quantity !== null) {
            $product = Product::lockForUpdate()->find($orderItem->product_id);
            if ($product) {
                $product->quantity += $orderItem->quantity;
                $product->save();
            }
        }
    });
}


    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
