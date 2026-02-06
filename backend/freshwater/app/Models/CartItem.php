<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /**
    * The attributes that are mass assignable.
    */
    protected $fillable = [
        'cart_id', 'product_id', 'quantity', 'price', 'total'
    ];

    /**
     * Define a belongs-to relationship to the Product model, indicating that each CartItem is associated with a single Product.
     */
    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    /**
     * Define a belongs-to relationship to the Cart model, indicating that each CartItem is associated with a single Cart.
     */
    public function cart() : BelongsTo
    {
        return $this->belongsTo(Cart::class);  
    }
}
