<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/CreateUser/CreateUserCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\CreateUser;

final readonly class CreateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phone,
        public readonly string $actorUserId,
    ) {}
}
