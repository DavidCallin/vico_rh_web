<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_with_unknown_email_returns_validation_error_instead_of_server_error(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'introuvable@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
