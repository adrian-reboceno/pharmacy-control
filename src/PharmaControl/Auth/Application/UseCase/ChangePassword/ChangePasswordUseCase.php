<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/ChangePassword/ChangePasswordUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\ChangePassword;

use PharmaControl\Auth\Domain\Contract\Repository\PasswordHistoryRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Exception\InvalidCredentialsException;
use PharmaControl\Auth\Domain\Policy\PasswordPolicy;
use PharmaControl\Auth\Domain\ValueObject\Password;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class ChangePasswordUseCase
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly SessionRepositoryContract $sessions,
        private readonly PasswordHistoryRepositoryContract $passwordHistory,
        private readonly EventPublisherContract $events,
        private readonly PasswordPolicy $passwordPolicy,
    ) {}

    public function execute(ChangePasswordCommand $command): void
    {
        $userId = new UserId($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('Usuario no encontrado.', 404);
        }

        if (! $user->getPasswordHash()->verify($command->currentPassword)) {
            throw new InvalidCredentialsException;
        }

        $newPassword = new Password($command->newPassword);
        $this->passwordPolicy->validate($newPassword);

        $newHash = $this->passwordPolicy->makeHash($command->newPassword);

        $history = $this->passwordHistory->findRecentByUser($userId, PasswordPolicy::HISTORY_COUNT);
        if ($this->passwordPolicy->wasRecentlyUsed($newHash, $history)) {
            throw new \InvalidArgumentException(
                'No puede reutilizar ninguna de sus últimas '.PasswordPolicy::HISTORY_COUNT.' contraseñas.'
            );
        }

        $oldHash = $user->getPasswordHash();
        $user->changePassword($newHash, $userId, false);

        $this->passwordHistory->save($userId, $oldHash);
        $this->users->save($user);

        $this->sessions->revokeAllByUser($userId);

        foreach ($user->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
