<?php

namespace App\Services;

use App\Models\Product;
use RuntimeException;

class StockService
{
    /**
     * Validate and reserve stock atomically.
     * 
     * @throws RuntimeException
     */

    public function reserve(Product $product, int $quantity): void
    {
        $affected = Product::where('id', $product->id)
                ->where('quantity', '>=', $quantity)
                ->decrement('quantity', $quantity);

        if($affected===0)
        {
            throw new RuntimeException("Недостатъчна наличност за продукт: {$product->name}");
        }
    }

    /**
     * Release stock (used only on rollback / cancel).
     */
    public function release(Product $product, int $quantity): void
    {
        Product::where('id', $product->id)
            ->increment('quantity', $quantity);
    }
}