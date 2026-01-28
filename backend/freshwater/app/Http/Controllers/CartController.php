<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(CartService $cart)
    {
        return response()->json([
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
        ]);
    }

    public function add(Request $request, CartService $cart)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        $cart->add($product, $request->quantity ?? 1);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
        ]);
    }

    public function update(Request $request, CartService $cart)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($request->product_id);
        $cart->update($product, $request->quantity);

        return response()->json(['success' => true]);
    }

    public function remove(Request $request, CartService $cart)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        $cart->remove($product);

        return response()->json(['success' => true]);
    }

    public function clear(CartService $cart)
    {
        $cart->clear();

        return response()->json(['success' => true]);
    }
}
