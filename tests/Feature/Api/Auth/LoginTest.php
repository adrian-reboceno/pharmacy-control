<?php

// ── ARCHIVO: tests/Feature/Api/Auth/LoginTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(array $overrides = []): EloquentUser
    {
        return EloquentUser::factory()->create(array_merge([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'ACTIVE',
            'must_change_password' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_returns_401_when_email_not_found(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'Secret123!',
            'client_type' => 'WEB',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_401_when_password_is_wrong(): void
    {
        $this->createActiveUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPass1!',
            'client_type' => 'WEB',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_200_with_tokens_on_valid_credentials(): void
    {
        $this->createActiveUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Secret123!',
            'client_type' => 'WEB',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'expires_in',
                    'token_type',
                    'requires_password_change',
                    'requires_role_selection',
                ],
            ]);
    }

    public function test_returns_422_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_returns_422_when_email_format_is_invalid(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_returns_423_when_account_is_locked(): void
    {
        $this->createActiveUser([
            'status' => 'LOCKED',
            'locked_until' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Secret123!',
            'client_type' => 'WEB',
        ]);

        $response->assertStatus(423);
    }

    public function test_returns_200_with_requires_password_change_true(): void
    {
        $this->createActiveUser(['must_change_password' => true]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Secret123!',
            'client_type' => 'WEB',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.requires_password_change', true);
    }

    public function test_increments_failed_attempts_in_database(): void
    {
        $user = $this->createActiveUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPass1!',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'failed_login_attempts' => 1,
        ]);
    }

    public function test_throttles_requests_after_five_attempts(): void
    {
        $this->createActiveUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'user@example.com',
                'password' => 'WrongPass!',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPass!',
        ]);

        $response->assertStatus(429);
    }
}
