<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
    ];

    public function parent() : BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children() : HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products() : BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public static function booted()
    {
        static::saving(function (Category $category){
            if($category->isDirty('name')){
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
