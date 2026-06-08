<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/UserController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Application\DTO\UserDTO;
use PharmaControl\Auth\Application\UseCase\CreateUser\CreateUserCommand;
use PharmaControl\Auth\Application\UseCase\CreateUser\CreateUserUseCase;
use PharmaControl\Auth\Application\UseCase\UnlockAccount\UnlockAccountCommand;
use PharmaControl\Auth\Application\UseCase\UnlockAccount\UnlockAccountUseCase;

final class UserController
{
    public function __construct(
        private readonly CreateUserUseCase $createUser,
        private readonly UnlockAccountUseCase $unlockAccount,
    ) {}

    public function create(array $data): UserDTO
    {
        return $this->createUser->execute(new CreateUserCommand(
            email: $data['email'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            phone: $data['phone'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function unlock(array $data): void
    {
        $this->unlockAccount->execute(new UnlockAccountCommand(
            targetUserId: $data['target_user_id'],
            actorUserId: $data['actor_user_id'],
        ));
    }
}
