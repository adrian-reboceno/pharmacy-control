<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Application/UseCase/ChangePasswordUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Application\UseCase;

use PharmaControl\Auth\Application\UseCase\ChangePassword\ChangePasswordCommand;
use PharmaControl\Auth\Application\UseCase\ChangePassword\ChangePasswordUseCase;
use PharmaControl\Auth\Domain\Contract\Repository\PasswordHistoryRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Exception\InvalidCredentialsException;
use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\Policy\PasswordPolicy;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChangePasswordUseCaseTest extends TestCase
{
    private UserRepositoryContract&MockObject $users;

    private SessionRepositoryContract&MockObject $sessions;

    private PasswordHistoryRepositoryContract&MockObject $history;

    private EventPublisherContract&MockObject $events;

    private ChangePasswordUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = $this->createMock(UserRepositoryContract::class);
        $this->sessions = $this->createMock(SessionRepositoryContract::class);
        $this->history = $this->createMock(PasswordHistoryRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);

        $this->useCase = new ChangePasswordUseCase(
            $this->users,
            $this->sessions,
            $this->history,
            $this->events,
            new PasswordPolicy,
        );
    }

    private function makeUser(string $password = 'CurrentPass1!'): User
    {
        return User::reconstitute(
            UserId::generate(), new Email('u@e.com'),
            new HashedPassword(password_hash($password, PASSWORD_BCRYPT, ['cost' => 4])),
            'U', 'E', null, UserStatus::ACTIVE, false, false, null, 0,
            null, null, null, null, null,
        );
    }

    public function test_changes_password_successfully(): void
    {
        $user = $this->makeUser('CurrentPass1!');

        $this->users->method('findById')->willReturn($user);
        $this->history->method('findRecentByUser')->willReturn([]);
        $this->history->expects($this->once())->method('save');
        $this->users->expects($this->once())->method('save');
        $this->sessions->expects($this->once())->method('revokeAllByUser');
        $this->events->expects($this->atLeastOnce())->method('publish');

        $this->useCase->execute(new ChangePasswordCommand(
            $user->id->value,
            'CurrentPass1!',
            'NewSecret99!',
        ));

        $this->addToAssertionCount(1);
    }

    public function test_throws_when_current_password_is_wrong(): void
    {
        $user = $this->makeUser('CurrentPass1!');
        $this->users->method('findById')->willReturn($user);

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase->execute(new ChangePasswordCommand(
            $user->id->value,
            'WrongPass1!',
            'NewSecret99!',
        ));
    }

    public function test_throws_when_new_password_was_recently_used(): void
    {
        $currentPassword = 'CurrentPass1!';
        $user = $this->makeUser($currentPassword);
        $recentHash = new HashedPassword(password_hash('NewSecret99!', PASSWORD_BCRYPT, ['cost' => 4]));

        $this->users->method('findById')->willReturn($user);
        $this->history->method('findRecentByUser')->willReturn([$recentHash]);

        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute(new ChangePasswordCommand(
            $user->id->value,
            $currentPassword,
            'NewSecret99!',
        ));
    }

    public function test_revokes_all_sessions_on_password_change(): void
    {
        $user = $this->makeUser('CurrentPass1!');

        $this->users->method('findById')->willReturn($user);
        $this->history->method('findRecentByUser')->willReturn([]);
        $this->sessions->expects($this->once())->method('revokeAllByUser');

        $this->useCase->execute(new ChangePasswordCommand(
            $user->id->value,
            'CurrentPass1!',
            'NewSecret99!',
        ));
    }

    public function test_publishes_password_changed_event(): void
    {
        $user = $this->makeUser('CurrentPass1!');

        $this->users->method('findById')->willReturn($user);
        $this->history->method('findRecentByUser')->willReturn([]);
        $this->events->expects($this->atLeastOnce())->method('publish');

        $this->useCase->execute(new ChangePasswordCommand(
            $user->id->value,
            'CurrentPass1!',
            'NewSecret99!',
        ));
    }
}
