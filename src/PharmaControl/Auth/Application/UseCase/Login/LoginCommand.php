<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/Login/LoginCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\Login;

final readonly class LoginCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $clientType,
        public readonly string $ipAddress,
        public readonly ?string $userAgent = null,
    ) {}
}
