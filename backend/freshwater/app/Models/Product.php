<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'sale_price',
        'stock',
        'slug',
        'short_description',
        'description',
        'extra_information',
        'quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
    public function relatedProducts() : BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_related',
            'product_id',
            'related_product_id'
        );
    }
    public function products(): BelongsToMany
    {
        // Point the default 'products' call to your 'relatedProducts' logic
        return $this->relatedProducts();
    }
    public function images() : HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
    public function primaryImage() : HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public static function booted()
    {
        static::saving(function (Product $product){
            if($product->isDirty('name')){
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
