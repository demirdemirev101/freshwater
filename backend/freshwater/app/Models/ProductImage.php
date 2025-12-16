<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected $hidden = ['image_path', 'created_at', 'updated_at'];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return  asset('storage/' . $this->image_path);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
