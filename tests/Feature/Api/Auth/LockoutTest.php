<?php

// ── ARCHIVO: tests/Feature/Api/Auth/LockoutTest.php ──
declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PharmaControl\Auth\Infrastructure\Job\NotifyAdminOnLockoutJob;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use Tests\TestCase;

final class LockoutTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): EloquentUser
    {
        return EloquentUser::factory()->create(array_merge([
            'email' => 'user@example.com',
            'password_hash' => Hash::make('CorrectPass1!'),
            'status' => 'ACTIVE',
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_account_is_locked_after_five_failed_attempts(): void
    {
        $this->createUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'user@example.com',
                'password' => 'WrongPass1!',
            ]);
        }

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'status' => 'LOCKED',
        ]);
    }

    public function test_locked_account_returns_423_even_with_correct_password(): void
    {
        $this->createUser([
            'status' => 'LOCKED',
            'locked_until' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'CorrectPass1!',
        ]);

        $response->assertStatus(423);
    }

    public function test_lockout_duration_doubles_on_each_reincidence(): void
    {
        $user = $this->createUser(['failed_login_attempts' => 5]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'user@example.com',
                'password' => 'WrongPass1!',
            ]);
        }

        $dbUser = EloquentUser::where('email', 'user@example.com')->first();
        $lockDuration = now()->diffInMinutes($dbUser->locked_until, absolute: true);
        self::assertGreaterThanOrEqual(30, $lockDuration);
    }

    public function test_admin_can_unlock_locked_account(): void
    {
        $lockedUser = $this->createUser([
            'status' => 'LOCKED',
            'locked_until' => now()->addHour(),
        ]);

        $admin = EloquentUser::factory()->create([
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('AdminPass1!'),
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super-admin');

        $adminTokenResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'AdminPass1!',
            'client_type' => 'WEB',
        ]);
        $adminToken = $adminTokenResponse->json('data.access_token');

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/auth/users/'.$lockedUser->id.'/unlock');

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $lockedUser->id,
            'status' => 'ACTIVE',
            'locked_until' => null,
        ]);
    }

    public function test_failed_attempts_reset_after_successful_login(): void
    {
        $this->createUser(['failed_login_attempts' => 3]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'CorrectPass1!',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'failed_login_attempts' => 0,
        ]);
    }

    public function test_notification_is_queued_when_account_gets_locked(): void
    {
        Queue::fake();
        $this->createUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'user@example.com',
                'password' => 'WrongPass1!',
            ]);
        }

        Queue::assertPushed(
            NotifyAdminOnLockoutJob::class
        );
    }
}
