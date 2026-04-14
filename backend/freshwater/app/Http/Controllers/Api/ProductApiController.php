<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductAPIResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    /**
     * Display a listing of the products, including their categories and images as a JSON resource collection.
      *
      * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $products = Product::with([
           'categories:id,name,slug',
            'images:id,product_id,image_path,is_primary,sort_order',
        ])->get();

        return ProductAPIResource::collection($products);
    }
}
