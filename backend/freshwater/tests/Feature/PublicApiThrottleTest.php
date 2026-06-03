<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicApiThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_endpoint_is_rate_limited(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Rate Limit Tester',
            'phone' => '0888123456',
            'email' => 'rate-limit@example.com',
            'message' => 'Test message',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/contact', $payload)->assertCreated();
        }

        $this->postJson('/api/contact', $payload)->assertStatus(429);
    }
}
