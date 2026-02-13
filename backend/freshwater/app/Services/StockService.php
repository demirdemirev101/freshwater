<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Product;
use RuntimeException;

class StockService
{
    /**
     * Reserve stock for a product. This method attempts to decrement the product's quantity by the specified amount.
     *  If the product does not have sufficient quantity available, it throws a CheckoutException with a message indicating insufficient stock for the product.
     *  The method uses an atomic database operation to ensure that the stock reservation is handled safely in concurrent environments.
     */

    public function reserve(Product $product, int $quantity): void
    {
        $affected = Product::where('id', $product->id)
                ->where('quantity', '>=', $quantity)
                ->decrement('quantity', $quantity);

        if($affected===0)
        {
            throw new CheckoutException("Недостатъчна наличност за продукт: {$product->name}", 409);
        }
    }

    /**
     * Release reserved stock for a product. This method increments the product's quantity by the specified amount,
     *  effectively releasing the reserved stock back into inventory.
     * The method uses an atomic database operation to ensure that the stock release is handled safely in concurrent environments.
     */
    public function release(Product $product, int $quantity): void
    {
        Product::where('id', $product->id)
            ->increment('quantity', $quantity);
    }
}