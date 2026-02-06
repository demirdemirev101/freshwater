<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'total',
    ];

    /**
     * Define a belongs-to relationship to the Order model, indicating that each OrderItem is associated with a single Order.
     */
    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Define a belongs-to relationship to the Product model, indicating that each OrderItem is associated with a single Product.
     */
    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
