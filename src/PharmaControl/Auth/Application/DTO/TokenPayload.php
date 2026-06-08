<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/DTO/TokenPayload.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\DTO;

final readonly class TokenPayload
{
    /** @param list<string> $permissions */
    public function __construct(
        public readonly string $sub,
        public readonly string $jti,
        public readonly string $roleId,
        public readonly string $roleName,
        public readonly ?string $branchId,
        public readonly array $permissions,
        public readonly string $sessionId,
        public readonly string $clientType,
        public readonly int $exp,
        public readonly int $iat,
    ) {}

    public static function fromArray(array $claims): self
    {
        return new self(
            sub: $claims['sub'],
            jti: $claims['jti'],
            roleId: $claims['role_id'],
            roleName: $claims['role'],
            branchId: $claims['branch_id'] ?? null,
            permissions: $claims['permissions'] ?? [],
            sessionId: $claims['session_id'],
            clientType: $claims['client_type'],
            exp: (int) $claims['exp'],
            iat: (int) $claims['iat'],
        );
    }

    public function isExpired(): bool
    {
        return $this->exp < time();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
