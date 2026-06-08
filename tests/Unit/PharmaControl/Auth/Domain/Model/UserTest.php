<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Domain/Model/UserTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Domain\Model;

use PharmaControl\Auth\Domain\Event\AccountLocked;
use PharmaControl\Auth\Domain\Event\AccountUnlocked;
use PharmaControl\Auth\Domain\Event\LoginFailed;
use PharmaControl\Auth\Domain\Event\UserRegistered;
use PharmaControl\Auth\Domain\Exception\AccountLockedException;
use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function makeUser(?UserStatus $status = null): User
    {
        $user = User::create(
            UserId::generate(),
            new Email('john@example.com'),
            new HashedPassword(password_hash('Secret123!', PASSWORD_BCRYPT, ['cost' => 4])),
            'John',
            'Doe',
        );
        // drain creation events
        $user->releaseEvents();

        return $user;
    }

    private function makeReconstituted(?UserStatus $status = null): User
    {
        return User::reconstitute(
            id: UserId::generate(),
            email: new Email('john@example.com'),
            passwordHash: new HashedPassword(password_hash('Secret123!', PASSWORD_BCRYPT, ['cost' => 4])),
            firstName: 'John',
            lastName: 'Doe',
            phone: null,
            status: $status ?? UserStatus::ACTIVE,
            mustChangePassword: false,
            twoFactorEnabled: false,
            twoFactorSecret: null,
            failedLoginAttempts: 0,
            lockedUntil: null,
            passwordChangedAt: null,
            lastLoginAt: null,
            lastActivityAt: null,
            emailVerifiedAt: null,
        );
    }

    public function test_creates_user_with_pending_verification_status_and_must_change_password(): void
    {
        $user = User::create(
            UserId::generate(),
            new Email('test@example.com'),
            new HashedPassword(password_hash('pass', PASSWORD_BCRYPT, ['cost' => 4])),
            'Test',
            'User',
        );

        self::assertSame(UserStatus::PENDING_VERIFICATION, $user->getStatus());
        self::assertTrue($user->mustChangePassword());
    }

    public function test_create_emits_user_registered_event(): void
    {
        $user = $this->makeUser();
        $user2 = User::create(
            UserId::generate(),
            new Email('e@x.com'),
            new HashedPassword(password_hash('p', PASSWORD_BCRYPT, ['cost' => 4])),
            'A', 'B',
        );
        $events = $user2->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $user = $this->makeReconstituted();
        $events = $user->releaseEvents();

        self::assertCount(0, $events);
    }

    public function test_lock_sets_locked_until_and_emits_account_locked(): void
    {
        $user = $this->makeReconstituted();
        $ip = new IpAddress('127.0.0.1');
        $user->lock(15, $ip);

        self::assertTrue($user->isLocked());
        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccountLocked::class, $events[0]);
    }

    public function test_is_locked_returns_true_when_locked_until_in_future(): void
    {
        $user = $this->makeReconstituted();
        $user->lock(60, new IpAddress('10.0.0.1'));

        self::assertTrue($user->isLocked());
    }

    public function test_is_locked_returns_false_when_locked_until_in_past(): void
    {
        $user = User::reconstitute(
            UserId::generate(), new Email('a@b.com'),
            new HashedPassword('x'), 'A', 'B', null,
            UserStatus::ACTIVE, false, false, null, 0,
            (new \DateTimeImmutable)->modify('-1 minute'),
            null, null, null, null,
        );

        self::assertFalse($user->isLocked());
    }

    public function test_unlock_resets_failed_attempts_and_locked_until_and_emits_event(): void
    {
        $user = $this->makeReconstituted();
        $user->lock(15, new IpAddress('127.0.0.1'));
        $user->releaseEvents();

        $user->unlock();

        self::assertFalse($user->isLocked());
        self::assertSame(0, $user->getFailedLoginAttempts());
        $events = $user->releaseEvents();
        self::assertInstanceOf(AccountUnlocked::class, $events[0]);
    }

    public function test_increment_failed_attempts_accumulates_and_emits_login_failed(): void
    {
        $user = $this->makeReconstituted();
        $ip = new IpAddress('1.2.3.4');

        $user->incrementFailedAttempts($ip);
        $user->incrementFailedAttempts($ip);

        self::assertSame(2, $user->getFailedLoginAttempts());
        $events = $user->releaseEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(LoginFailed::class, $events[0]);
        self::assertInstanceOf(LoginFailed::class, $events[1]);
    }

    public function test_reset_failed_attempts_sets_counter_to_zero(): void
    {
        $user = $this->makeReconstituted();
        $user->incrementFailedAttempts(new IpAddress('1.1.1.1'));
        $user->releaseEvents();

        $user->resetFailedAttempts();

        self::assertSame(0, $user->getFailedLoginAttempts());
    }

    public function test_assert_not_locked_throws_account_locked_exception_when_locked(): void
    {
        $user = $this->makeReconstituted();
        $user->lock(15, new IpAddress('1.1.1.1'));
        $user->releaseEvents();

        $this->expectException(AccountLockedException::class);
        $user->assertNotLocked();
    }

    public function test_assert_not_locked_passes_when_not_locked(): void
    {
        $user = $this->makeReconstituted();
        $user->assertNotLocked();

        $this->addToAssertionCount(1);
    }

    public function test_is_password_expired_returns_true_when_rotation_period_exceeded(): void
    {
        $user = User::reconstitute(
            UserId::generate(), new Email('a@b.com'),
            new HashedPassword('x'), 'A', 'B', null,
            UserStatus::ACTIVE, false, false, null, 0, null,
            (new \DateTimeImmutable)->modify('-200 days'),
            null, null, null,
        );

        self::assertTrue($user->isPasswordExpired(180));
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $user = User::create(
            UserId::generate(),
            new Email('x@y.com'),
            new HashedPassword(password_hash('p', PASSWORD_BCRYPT, ['cost' => 4])),
            'X', 'Y',
        );

        $first = $user->releaseEvents();
        $second = $user->releaseEvents();

        self::assertCount(1, $first);
        self::assertCount(0, $second);
    }

    public function test_inactive_user_cannot_be_locked(): void
    {
        $user = $this->makeReconstituted(UserStatus::INACTIVE);

        $this->expectException(\LogicException::class);
        $user->lock(15, new IpAddress('1.1.1.1'));
    }
}
