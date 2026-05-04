<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthGuestCartMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_merges_guest_cart_sent_as_snake_case_session_id(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'price' => 100,
            'sale_price' => 80,
            'quantity' => 10,
            'stock' => true,
        ]);

        $sessionId = 'fw-cart-login-merge';

        $this->postJson("/api/cart/add/{$product->id}?session_id={$sessionId}", [
            'quantity' => 2,
        ])->assertOk();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'session_id' => $sessionId,
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('cart.items.0.product_id', $product->id)
            ->assertJsonPath('cart.items.0.quantity', 2);

        $this->assertDatabaseMissing('carts', [
            'session_id' => $sessionId,
        ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function test_login_does_not_fail_when_optional_cart_session_id_is_null(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer-null-session@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->withHeader('User-Agent', 'auth-null-session-test')->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'session_id' => null,
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_uses_remembered_frontend_cart_session_when_payload_session_id_is_null(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer-fallback-session@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $product = Product::create([
            'name' => 'Fallback Product',
            'price' => 55,
            'sale_price' => null,
            'quantity' => 10,
            'stock' => true,
        ]);

        $sessionId = 'fw-cart-login-fallback';
        $userAgent = 'auth-cart-fallback-test';

        $this->withHeader('User-Agent', $userAgent)->postJson("/api/cart/add/{$product->id}?session_id={$sessionId}", [
            'quantity' => 1,
        ])->assertOk();

        $this->withHeader('User-Agent', $userAgent)->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'session_id' => null,
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('cart.items.0.product_id', $product->id)
            ->assertJsonPath('cart.items.0.quantity', 1);

        $this->assertDatabaseMissing('carts', [
            'session_id' => $sessionId,
        ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function test_cart_routes_use_user_cart_after_merge_when_frontend_keeps_sending_guest_session_id(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer-after-merge@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $firstProduct = Product::create([
            'name' => 'Merged Product',
            'price' => 40,
            'sale_price' => null,
            'quantity' => 10,
            'stock' => true,
        ]);

        $secondProduct = Product::create([
            'name' => 'Post Login Product',
            'price' => 25,
            'sale_price' => null,
            'quantity' => 10,
            'stock' => true,
        ]);

        $sessionId = 'fw-cart-after-merge';
        $userAgent = 'auth-cart-after-merge-test';

        $this->withHeader('User-Agent', $userAgent)->postJson("/api/cart/add/{$firstProduct->id}?session_id={$sessionId}", [
            'quantity' => 1,
        ])->assertOk();

        $this->withHeader('User-Agent', $userAgent)->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'session_id' => $sessionId,
        ])->assertOk();

        $this->withHeader('User-Agent', $userAgent)->postJson("/api/cart/add/{$secondProduct->id}?session_id={$sessionId}", [
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('items.0.product_id', $firstProduct->id)
            ->assertJsonPath('items.1.product_id', $secondProduct->id);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);

        $this->assertDatabaseMissing('carts', [
            'session_id' => $sessionId,
        ]);
    }
}
