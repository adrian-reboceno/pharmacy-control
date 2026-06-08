<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/RefreshToken/RefreshTokenCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\RefreshToken;

final readonly class RefreshTokenCommand
{
    public function __construct(
        public readonly string $refreshToken,
        public readonly string $ipAddress,
        public readonly ?string $userAgent = null,
    ) {}
}
