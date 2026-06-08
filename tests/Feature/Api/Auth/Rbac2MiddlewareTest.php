<?php

// ── ARCHIVO: tests/Feature/Api/Auth/Rbac2MiddlewareTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use Tests\TestCase;

final class Rbac2MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsUser(array $overrides = []): array
    {
        EloquentUser::factory()->create(array_merge([
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
        ], $overrides));

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'Secret123!',
            'client_type' => 'WEB',
        ]);

        return $response->json('data') ?? [];
    }

    public function test_returns_401_when_bearer_token_is_absent(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_returns_401_when_bearer_token_is_malformed(): void
    {
        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer not.a.valid.jwt',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_200_when_valid_bearer_token_is_present(): void
    {
        $tokens = $this->loginAsUser();

        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ]);

        $response->assertStatus(200);
    }

    public function test_returns_401_when_token_is_blacklisted(): void
    {
        $tokens = $this->loginAsUser();

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_403_when_permission_is_insufficient(): void
    {
        $tokens = $this->loginAsUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/v1/auth/users', [
                'email' => 'new@example.com',
                'first_name' => 'New',
                'last_name' => 'User',
            ]);

        $response->assertStatus(403);
    }

    public function test_injects_authenticated_user_into_request_attributes(): void
    {
        $tokens = $this->loginAsUser();

        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'admin@example.com');
    }
}
