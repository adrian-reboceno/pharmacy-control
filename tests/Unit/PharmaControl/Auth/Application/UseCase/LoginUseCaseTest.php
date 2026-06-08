<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Application/UseCase/LoginUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Application\UseCase;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Application\UseCase\Login\LoginCommand;
use PharmaControl\Auth\Application\UseCase\Login\LoginUseCase;
use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\RateLimiterServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\Exception\AccountLockedException;
use PharmaControl\Auth\Domain\Exception\InvalidCredentialsException;
use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\Policy\LockoutPolicy;
use PharmaControl\Auth\Domain\Policy\PasswordPolicy;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LoginUseCaseTest extends TestCase
{
    private UserRepositoryContract&MockObject $users;

    private SessionRepositoryContract&MockObject $sessions;

    private RoleRepositoryContract&MockObject $roles;

    private AuditLogRepositoryContract&MockObject $auditLog;

    private TokenServiceContract&MockObject $tokens;

    private RateLimiterServiceContract&MockObject $rateLimiter;

    private NotificationServiceContract&MockObject $notifications;

    private EventPublisherContract&MockObject $events;

    private LoginUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = $this->createMock(UserRepositoryContract::class);
        $this->sessions = $this->createMock(SessionRepositoryContract::class);
        $this->roles = $this->createMock(RoleRepositoryContract::class);
        $this->auditLog = $this->createMock(AuditLogRepositoryContract::class);
        $this->tokens = $this->createMock(TokenServiceContract::class);
        $this->rateLimiter = $this->createMock(RateLimiterServiceContract::class);
        $this->notifications = $this->createMock(NotificationServiceContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);

        $this->useCase = new LoginUseCase(
            $this->users,
            $this->sessions,
            $this->roles,
            $this->auditLog,
            $this->tokens,
            $this->rateLimiter,
            $this->notifications,
            $this->events,
            new LockoutPolicy,
            new PasswordPolicy,
        );
    }

    private function makeActiveUser(string $password = 'Secret123!'): User
    {
        return User::reconstitute(
            id: UserId::generate(),
            email: new Email('john@example.com'),
            passwordHash: new HashedPassword(password_hash($password, PASSWORD_BCRYPT, ['cost' => 4])),
            firstName: 'John',
            lastName: 'Doe',
            phone: null,
            status: UserStatus::ACTIVE,
            mustChangePassword: false,
            twoFactorEnabled: false,
            twoFactorSecret: null,
            failedLoginAttempts: 0,
            lockedUntil: null,
            passwordChangedAt: (new \DateTimeImmutable)->modify('-30 days'),
            lastLoginAt: null,
            lastActivityAt: null,
            emailVerifiedAt: (new \DateTimeImmutable),
        );
    }

    private function makeSingleRole(): Role
    {
        return Role::create(RoleId::generate(), 'pharmacist', 'Pharmacist', 6, false);
    }

    private function makeTokenData(): array
    {
        return [
            'accessToken' => 'mock.access.token',
            'refreshToken' => 'mock_refresh_token',
            'expiresIn' => 900,
            'jti' => 'mock-jti-123',
        ];
    }

    public function test_returns_auth_token_dto_on_valid_credentials_with_single_role(): void
    {
        $user = $this->makeActiveUser();
        $role = $this->makeSingleRole();

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->roles->method('getCurrentRoles')->willReturn([$role]);
        $this->roles->method('resolvePermissions')->willReturn([]);
        $this->tokens->method('issue')->willReturn($this->makeTokenData());
        $this->sessions->expects($this->once())->method('save');
        $this->auditLog->expects($this->once())->method('append');

        $result = $this->useCase->execute(new LoginCommand(
            'john@example.com', 'Secret123!', 'WEB', '127.0.0.1'
        ));

        self::assertInstanceOf(AuthTokenDTO::class, $result);
        self::assertFalse($result->requiresPasswordChange);
        self::assertFalse($result->requiresRoleSelection);
        self::assertSame('mock.access.token', $result->accessToken);
    }

    public function test_returns_requires_role_selection_true_when_user_has_multiple_roles(): void
    {
        $user = $this->makeActiveUser();
        $role1 = $this->makeSingleRole();
        $role2 = Role::create(RoleId::generate(), 'cashier', 'Cashier', 8, false);

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->roles->method('getCurrentRoles')->willReturn([$role1, $role2]);

        $result = $this->useCase->execute(new LoginCommand(
            'john@example.com', 'Secret123!', 'WEB', '127.0.0.1'
        ));

        self::assertTrue($result->requiresRoleSelection);
        self::assertCount(2, $result->availableRoles);
    }

    public function test_returns_requires_password_change_true_when_must_change_password(): void
    {
        $user = User::reconstitute(
            UserId::generate(), new Email('j@e.com'),
            new HashedPassword(password_hash('Secret123!', PASSWORD_BCRYPT, ['cost' => 4])),
            'J', 'D', null, UserStatus::ACTIVE, true, false, null, 0,
            null, null, null, null, null,
        );

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);

        $result = $this->useCase->execute(new LoginCommand(
            'j@e.com', 'Secret123!', 'WEB', '127.0.0.1'
        ));

        self::assertTrue($result->requiresPasswordChange);
    }

    public function test_throws_account_locked_exception_when_user_is_locked(): void
    {
        $user = User::reconstitute(
            UserId::generate(), new Email('j@e.com'),
            new HashedPassword('h'), 'J', 'D', null,
            UserStatus::LOCKED, false, false, null, 5,
            (new \DateTimeImmutable)->modify('+1 hour'),
            null, null, null, null,
        );

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);

        $this->expectException(AccountLockedException::class);
        $this->useCase->execute(new LoginCommand('j@e.com', 'pass', 'WEB', '127.0.0.1'));
    }

    public function test_throws_invalid_credentials_exception_on_wrong_password(): void
    {
        $user = $this->makeActiveUser('CorrectPass1!');

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->users->method('save');

        $this->expectException(InvalidCredentialsException::class);
        $this->useCase->execute(new LoginCommand('john@example.com', 'WrongPass1!', 'WEB', '1.1.1.1'));
    }

    public function test_increments_failed_attempts_on_wrong_password(): void
    {
        $user = $this->makeActiveUser('CorrectPass1!');

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->users->expects($this->once())->method('save');

        try {
            $this->useCase->execute(new LoginCommand('john@example.com', 'WrongPass!', 'WEB', '1.1.1.1'));
        } catch (InvalidCredentialsException) {
        }

        self::assertSame(1, $user->getFailedLoginAttempts());
    }

    public function test_locks_account_on_fifth_failed_attempt(): void
    {
        $user = User::reconstitute(
            UserId::generate(), new Email('j@e.com'),
            new HashedPassword(password_hash('Correct1!', PASSWORD_BCRYPT, ['cost' => 4])),
            'J', 'D', null, UserStatus::ACTIVE, false, false, null, 4,
            null, null, null, null, null,
        );

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->notifications->expects($this->once())->method('sendLockNotification');

        try {
            $this->useCase->execute(new LoginCommand('j@e.com', 'WrongPass1!', 'WEB', '1.1.1.1'));
        } catch (InvalidCredentialsException) {
        }

        self::assertTrue($user->isLocked());
    }

    public function test_resets_failed_attempts_on_successful_login(): void
    {
        $user = User::reconstitute(
            UserId::generate(), new Email('j@e.com'),
            new HashedPassword(password_hash('Secret123!', PASSWORD_BCRYPT, ['cost' => 4])),
            'J', 'D', null, UserStatus::ACTIVE, false, false, null, 3,
            (new \DateTimeImmutable)->modify('-10 days'),
            null, null, null, null,
        );
        $role = $this->makeSingleRole();

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->roles->method('getCurrentRoles')->willReturn([$role]);
        $this->roles->method('resolvePermissions')->willReturn([]);
        $this->tokens->method('issue')->willReturn($this->makeTokenData());
        $this->sessions->method('save');
        $this->auditLog->method('append');

        $this->useCase->execute(new LoginCommand('j@e.com', 'Secret123!', 'WEB', '1.1.1.1'));

        self::assertSame(0, $user->getFailedLoginAttempts());
    }

    public function test_publishes_login_succeeded_event_on_success(): void
    {
        $user = $this->makeActiveUser();
        $role = $this->makeSingleRole();

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->roles->method('getCurrentRoles')->willReturn([$role]);
        $this->roles->method('resolvePermissions')->willReturn([]);
        $this->tokens->method('issue')->willReturn($this->makeTokenData());
        $this->sessions->method('save');
        $this->auditLog->method('append');

        $this->events->expects($this->atLeastOnce())->method('publish');

        $this->useCase->execute(new LoginCommand('john@example.com', 'Secret123!', 'WEB', '1.1.1.1'));
    }

    public function test_publishes_login_failed_event_on_failure(): void
    {
        $user = $this->makeActiveUser('RealPass1!');

        $this->rateLimiter->method('tooManyAttempts')->willReturn(false);
        $this->users->method('findByEmail')->willReturn($user);
        $this->events->expects($this->atLeastOnce())->method('publish');

        try {
            $this->useCase->execute(new LoginCommand('john@example.com', 'WrongPass1!', 'WEB', '1.1.1.1'));
        } catch (InvalidCredentialsException) {
        }
    }
}
