<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'delivery_price',
        'free_delivery_over',
        'delivery_enabled',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }
        
}
