<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_id', 'session_id'];

    /**
     * Define a has-many relationship to the CartItem model, indicating that each Cart can have multiple CartItems.
     */
    public function items() : HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
