<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/Logout/LogoutCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\Logout;

final readonly class LogoutCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly string $jti,
        public readonly int $tokenTtlRemaining,
    ) {}
}
