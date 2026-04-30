<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartUpdateResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_route_returns_the_updated_cart_item_instead_of_an_empty_cart(): void
    {
        $product = Product::create([
            'name' => 'Updated Product',
            'price' => 19.90,
            'sale_price' => null,
            'quantity' => 15,
            'stock' => true,
        ]);

        $sessionId = 'fw-cart-update-response';

        $this->postJson("/api/cart/add/{$product->id}?session_id={$sessionId}", [
            'quantity' => 1,
        ])->assertOk();

        $this->patchJson("/api/cart/update/{$product->id}?session_id={$sessionId}", [
            'quantity' => 4,
        ])->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('session_id', $sessionId)
            ->assertJsonPath('items.0.product_id', $product->id)
            ->assertJsonPath('items.0.quantity', 4)
            ->assertJsonPath('subtotal', 79.6);
    }

    public function test_update_route_rejects_quantity_below_one(): void
    {
        $product = Product::create([
            'name' => 'Removed Product',
            'price' => 19.90,
            'sale_price' => null,
            'quantity' => 15,
            'stock' => true,
        ]);

        $sessionId = 'fw-cart-remove-on-zero';

        $this->postJson("/api/cart/add/{$product->id}?session_id={$sessionId}", [
            'quantity' => 1,
        ])->assertOk();

        $this->patchJson("/api/cart/update/{$product->id}?session_id={$sessionId}", [
            'quantity' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }
}
