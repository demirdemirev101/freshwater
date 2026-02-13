<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAPIResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,

            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'stock' => $this->stock,
            'quantity' => $this->quantity,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'extra_information' => $this->extra_information,
            'categories' => $this->categories,
            'images' => $this->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'is_primary' => (bool) $image->is_primary,
                    'sort_order' => $image->sort_order,
                    'url' => asset('storage/' . $image->image_path),
                ];
            }),
        ];  
    }
}
