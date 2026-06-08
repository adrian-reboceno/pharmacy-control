<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/DTO/AuthTokenDTO.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\DTO;

final readonly class AuthTokenDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $expiresIn,
        public readonly string $tokenType,
        public readonly bool $requiresPasswordChange,
        public readonly bool $requiresRoleSelection,
        public readonly ?array $availableRoles,
        public readonly ?string $activeRoleId,
        public readonly ?string $activeBranchId,
    ) {}
}
