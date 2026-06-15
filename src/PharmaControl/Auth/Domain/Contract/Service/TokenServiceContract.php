<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Service/TokenServiceContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Service;

use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\PermissionName;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\ValueObject\BranchId;

interface TokenServiceContract
{
    /**
     * @param  list<PermissionName>  $permissions
     * @return array{accessToken: string, refreshToken: string, expiresIn: int, jti: string}
     */
    public function issue(
        UserId $userId,
        RoleId $activeRoleId,
        ?BranchId $branchId,
        array $permissions,
        ClientType $clientType,
        SessionId $sessionId,
        string $firstName = '',
        string $lastName = '',
    ): array;

    /**
     * @return array{sub: string, jti: string, role_id: string, role: string, branch_id: ?string,
     *               permissions: string[], session_id: string, client_type: string, exp: int, iat: int}
     */
    public function verify(string $token): array;

    public function blacklist(string $jti, int $ttlSeconds): void;

    public function isBlacklisted(string $jti): bool;
}
