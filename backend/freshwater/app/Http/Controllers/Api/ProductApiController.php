<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductAPIResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function index()
    {
        $products = Product::with([
           'categories:id,name,slug',
            'images:id,product_id,image_path,is_primary,sort_order',
        ])->get();

        return ProductAPIResource::collection($products);
    }
}
