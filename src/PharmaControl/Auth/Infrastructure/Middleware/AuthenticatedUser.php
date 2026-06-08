<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Middleware/AuthenticatedUser.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Middleware;

final readonly class AuthenticatedUser
{
    /** @param list<string> $permissions */
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $activeRoleId,
        public readonly string $activeRoleName,
        public readonly ?string $activeBranchId,
        public readonly array $permissions,
        public readonly string $sessionId,
        public readonly string $jti,
        public readonly int $tokenExp,
    ) {}

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function tokenTtlRemaining(): int
    {
        return max(0, $this->tokenExp - time());
    }
}
