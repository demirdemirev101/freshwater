<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    /**
     * Cast is_primary to boolean for consistent handling in the application.
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Hide the image_path, created_at, and updated_at fields when serializing the model to JSON.
     */
    protected $hidden = ['image_path', 'created_at', 'updated_at'];
    /**
     * Append a custom URL attribute to the model's array and JSON representations, 
     *  which provides the full URL to access the image based on the stored image_path.
     */
    protected $appends = ['url'];

    /**
     * Accessor to get the full URL of the product image based on the stored image_path.
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
    /**
     * Define a belongs-to relationship to the Product model, indicating that each ProductImage is associated with a single Product.
     */
    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
