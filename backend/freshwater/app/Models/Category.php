<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
    ];

    /**
     * Define a belongs-to relationship to the parent Category model, indicating that each Category may have a single parent Category.
     */
    public function parent() : BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Define a has-many relationship to the child Category models, indicating that each Category can have multiple child Categories.
     */
    public function children() : HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Define a many-to-many relationship to the Product model, indicating that each Category can be associated with multiple Products.
     */
    public function products() : BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Boot method to handle model events.
     * Automatically generates and sets the slug attribute based on the name attribute whenever the Category is being saved.
    */
    public static function booted()
    {
        static::saving(function (Category $category){
            if($category->isDirty('name')){
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
