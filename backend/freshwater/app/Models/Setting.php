<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;
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
