<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    /**
     * Use the HasFactory trait to enable factory methods for the Setting model.
     */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'delivery_price',
        'free_delivery_over',
        'delivery_enabled',
    ];
    /** 
     * Retrieve the current application settings. If no settings exist, create a new default instance.
     */
    public static function current(): self
    {
        return static::firstOrCreate([]);
    }
        
}
