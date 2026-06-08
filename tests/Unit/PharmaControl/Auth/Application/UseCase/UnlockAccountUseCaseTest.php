<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Application/UseCase/UnlockAccountUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Application\UseCase;

use PharmaControl\Auth\Application\UseCase\UnlockAccount\UnlockAccountCommand;
use PharmaControl\Auth\Application\UseCase\UnlockAccount\UnlockAccountUseCase;
use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UnlockAccountUseCaseTest extends TestCase
{
    private UserRepositoryContract&MockObject $users;

    private AuditLogRepositoryContract&MockObject $auditLog;

    private NotificationServiceContract&MockObject $notifications;

    private EventPublisherContract&MockObject $events;

    private UnlockAccountUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = $this->createMock(UserRepositoryContract::class);
        $this->auditLog = $this->createMock(AuditLogRepositoryContract::class);
        $this->notifications = $this->createMock(NotificationServiceContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);

        $this->useCase = new UnlockAccountUseCase(
            $this->users,
            $this->auditLog,
            $this->notifications,
            $this->events,
        );
    }

    private function makeLockedUser(): User
    {
        $user = User::reconstitute(
            UserId::generate(), new Email('locked@example.com'),
            new HashedPassword('hash'), 'Locked', 'User', null,
            UserStatus::LOCKED, false, false, null, 5,
            (new \DateTimeImmutable)->modify('+1 hour'),
            null, null, null, null,
        );

        return $user;
    }

    private function makeActiveUser(): User
    {
        return User::reconstitute(
            UserId::generate(), new Email('active@example.com'),
            new HashedPassword('hash'), 'Active', 'User', null,
            UserStatus::ACTIVE, false, false, null, 0,
            null, null, null, null, null,
        );
    }

    public function test_unlocks_account_and_sends_notification_email(): void
    {
        $user = $this->makeLockedUser();
        $actor = UserId::generate();

        $this->users->method('findById')->willReturn($user);
        $this->notifications->expects($this->once())->method('sendUnlockEmail');
        $this->users->expects($this->once())->method('save');
        $this->auditLog->expects($this->once())->method('append');

        $this->useCase->execute(new UnlockAccountCommand($user->id->value, $actor->value));

        self::assertFalse($user->isLocked());
    }

    public function test_throws_when_target_user_is_not_locked(): void
    {
        $user = $this->makeActiveUser();
        $actor = UserId::generate();

        $this->users->method('findById')->willReturn($user);

        $this->expectException(\RuntimeException::class);

        $this->useCase->execute(new UnlockAccountCommand($user->id->value, $actor->value));
    }

    public function test_publishes_account_unlocked_event(): void
    {
        $user = $this->makeLockedUser();
        $actor = UserId::generate();

        $this->users->method('findById')->willReturn($user);
        $this->events->expects($this->atLeastOnce())->method('publish');

        $this->useCase->execute(new UnlockAccountCommand($user->id->value, $actor->value));
    }
}
