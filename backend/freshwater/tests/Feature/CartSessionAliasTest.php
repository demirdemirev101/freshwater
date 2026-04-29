<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartSessionAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_endpoints_accept_camel_case_session_id(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 12.50,
            'sale_price' => null,
            'quantity' => 50,
            'stock' => true,
        ]);

        $sessionId = 'fw-cart-react-session';

        $this->postJson("/api/cart/add/{$product->id}?sessionId={$sessionId}", [
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('session_id', $sessionId)
            ->assertJsonPath('items.0.product_id', $product->id)
            ->assertJsonPath('items.0.quantity', 1);

        $this->patchJson("/api/cart/update/{$product->id}?sessionId={$sessionId}", [
            'quantity' => 3,
        ])->assertOk()
            ->assertJsonPath('session_id', $sessionId)
            ->assertJsonPath('items.0.product_id', $product->id)
            ->assertJsonPath('items.0.quantity', 3);

        $this->getJson("/api/cart?sessionId={$sessionId}")
            ->assertOk()
            ->assertJsonPath('session_id', $sessionId)
            ->assertJsonPath('items.0.product_id', $product->id)
            ->assertJsonPath('items.0.quantity', 3);

        $this->assertDatabaseHas('carts', [
            'session_id' => $sessionId,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }
}
