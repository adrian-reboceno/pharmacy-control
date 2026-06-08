<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Mapper/SessionMapper.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\Model\UserSession;
use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUserSession;
use PharmaControl\Shared\ValueObject\BranchId;

final class SessionMapper
{
    public function toDomain(EloquentUserSession $eloquent): UserSession
    {
        return UserSession::reconstitute(
            id: new SessionId($eloquent->id),
            userId: new UserId($eloquent->user_id),
            clientType: ClientType::from($eloquent->client_type),
            accessTokenHash: $eloquent->access_token_hash,
            refreshTokenHash: $eloquent->refresh_token_hash,
            activeRoleId: new RoleId($eloquent->active_role_id),
            activeBranchId: $eloquent->active_branch_id ? new BranchId($eloquent->active_branch_id) : null,
            accessExpiresAt: $this->toImmutable($eloquent->access_expires_at),
            refreshExpiresAt: $this->toImmutable($eloquent->refresh_expires_at),
            lastActivityAt: $this->toImmutable($eloquent->last_activity_at),
            roleActivatedAt: $this->toImmutable($eloquent->role_activated_at),
            ipAddress: new IpAddress($eloquent->ip_address),
            userAgent: $eloquent->user_agent,
            revokedAt: $eloquent->revoked_at ? $this->toImmutable($eloquent->revoked_at) : null,
        );
    }

    public function toPersistence(UserSession $session): array
    {
        return [
            'id' => $session->id->value,
            'user_id' => $session->userId->value,
            'client_type' => $session->clientType->value,
            'access_token_hash' => $session->getAccessTokenHash(),
            'refresh_token_hash' => $session->getRefreshTokenHash(),
            'active_role_id' => $session->getActiveRoleId()->value,
            'active_branch_id' => $session->getActiveBranchId()?->value,
            'access_expires_at' => $session->accessExpiresAt->format('Y-m-d H:i:s'),
            'refresh_expires_at' => $session->refreshExpiresAt->format('Y-m-d H:i:s'),
            'last_activity_at' => $session->getLastActivityAt()->format('Y-m-d H:i:s'),
            'role_activated_at' => $session->roleActivatedAt->format('Y-m-d H:i:s'),
            'ip_address' => $session->ipAddress->value,
            'user_agent' => $session->userAgent,
            'revoked_at' => $session->getRevokedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function toImmutable(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        return new \DateTimeImmutable((string) $value);
    }
}
