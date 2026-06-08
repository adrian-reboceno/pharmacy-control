<?php

// ── ARCHIVO: tests/Feature/Api/Auth/RefreshTokenTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use Tests\TestCase;

final class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private function loginAndGetTokens(): array
    {
        $user = EloquentUser::factory()->create([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Secret123!',
            'client_type' => 'WEB',
        ]);

        return $response->json('data');
    }

    public function test_returns_new_token_pair_on_valid_refresh_token(): void
    {
        $tokens = $this->loginAndGetTokens();

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['access_token', 'refresh_token', 'expires_in'],
            ]);
    }

    public function test_returns_401_when_refresh_token_is_invalid(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid.refresh.token',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_401_when_refresh_token_has_been_revoked(): void
    {
        $tokens = $this->loginAndGetTokens();

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ]);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertStatus(401);
    }

    public function test_old_refresh_token_is_invalidated_after_rotation(): void
    {
        $tokens = $this->loginAndGetTokens();
        $oldToken = $tokens['refresh_token'];

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $oldToken,
        ]);

        $response2 = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $oldToken,
        ]);

        $response2->assertStatus(401);
    }

    public function test_returns_422_when_refresh_token_field_is_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }
}
