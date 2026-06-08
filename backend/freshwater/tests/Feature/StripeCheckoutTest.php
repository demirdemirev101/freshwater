<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_methods_endpoint_hides_stripe_when_disabled(): void
    {
        Setting::current()->update([
            'stripe_enabled' => false,
        ]);

        $response = $this->getJson('/api/checkout/payment-methods');

        $response->assertOk()
            ->assertJson([
                'stripe_enabled' => false,
            ]);

        $this->assertSame(
            ['bank_transfer', 'cod'],
            array_column($response->json('payment_methods'), 'value'),
        );
    }

    public function test_payment_methods_endpoint_includes_stripe_when_enabled(): void
    {
        Setting::current()->update([
            'stripe_enabled' => true,
        ]);

        $response = $this->getJson('/api/checkout/payment-methods');

        $response->assertOk()
            ->assertJson([
                'stripe_enabled' => true,
            ]);

        $this->assertSame(
            ['bank_transfer', 'cod', 'stripe'],
            array_column($response->json('payment_methods'), 'value'),
        );
    }

    public function test_checkout_rejects_stripe_when_it_is_disabled(): void
    {
        Setting::current()->update([
            'stripe_enabled' => false,
        ]);

        $product = Product::create([
            'name' => 'Compact MF',
            'price' => 99,
            'stock' => true,
            'quantity' => 10,
        ]);

        $response = $this->postJson('/api/checkout', [
            'customer_name' => 'Ivan Petrov',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '0888123456',
            'shipping_method' => 'address',
            'shipping_address' => 'Tsarigradsko shose 10',
            'shipping_city' => 'Sofia',
            'shipping_postcode' => '1784',
            'payment_method' => 'stripe',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Stripe плащанията са временно изключени.',
            ]);
    }
}
