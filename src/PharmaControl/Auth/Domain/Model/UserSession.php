<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Model/UserSession.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\ValueObject\BranchId;

final class UserSession
{
    private string $refreshTokenHash;

    private string $accessTokenHash;

    private RoleId $activeRoleId;

    private ?BranchId $activeBranchId;

    private \DateTimeImmutable $lastActivityAt;

    private ?\DateTimeImmutable $revokedAt;

    private function __construct(
        public readonly SessionId $id,
        public readonly UserId $userId,
        public readonly ClientType $clientType,
        string $accessTokenHash,
        string $refreshTokenHash,
        RoleId $activeRoleId,
        ?BranchId $activeBranchId,
        public readonly \DateTimeImmutable $accessExpiresAt,
        public readonly \DateTimeImmutable $refreshExpiresAt,
        \DateTimeImmutable $lastActivityAt,
        public readonly \DateTimeImmutable $roleActivatedAt,
        public readonly IpAddress $ipAddress,
        public readonly ?string $userAgent,
        ?\DateTimeImmutable $revokedAt,
    ) {
        $this->accessTokenHash = $accessTokenHash;
        $this->refreshTokenHash = $refreshTokenHash;
        $this->activeRoleId = $activeRoleId;
        $this->activeBranchId = $activeBranchId;
        $this->lastActivityAt = $lastActivityAt;
        $this->revokedAt = $revokedAt;
    }

    public static function create(
        SessionId $id,
        UserId $userId,
        ClientType $clientType,
        string $accessTokenHash,
        string $refreshTokenHash,
        RoleId $activeRoleId,
        ?BranchId $activeBranchId,
        \DateTimeImmutable $accessExpiresAt,
        \DateTimeImmutable $refreshExpiresAt,
        IpAddress $ipAddress,
        ?string $userAgent,
    ): self {
        $now = new \DateTimeImmutable;

        return new self(
            id: $id,
            userId: $userId,
            clientType: $clientType,
            accessTokenHash: $accessTokenHash,
            refreshTokenHash: $refreshTokenHash,
            activeRoleId: $activeRoleId,
            activeBranchId: $activeBranchId,
            accessExpiresAt: $accessExpiresAt,
            refreshExpiresAt: $refreshExpiresAt,
            lastActivityAt: $now,
            roleActivatedAt: $now,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            revokedAt: null,
        );
    }

    public static function reconstitute(
        SessionId $id,
        UserId $userId,
        ClientType $clientType,
        string $accessTokenHash,
        string $refreshTokenHash,
        RoleId $activeRoleId,
        ?BranchId $activeBranchId,
        \DateTimeImmutable $accessExpiresAt,
        \DateTimeImmutable $refreshExpiresAt,
        \DateTimeImmutable $lastActivityAt,
        \DateTimeImmutable $roleActivatedAt,
        IpAddress $ipAddress,
        ?string $userAgent,
        ?\DateTimeImmutable $revokedAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            clientType: $clientType,
            accessTokenHash: $accessTokenHash,
            refreshTokenHash: $refreshTokenHash,
            activeRoleId: $activeRoleId,
            activeBranchId: $activeBranchId,
            accessExpiresAt: $accessExpiresAt,
            refreshExpiresAt: $refreshExpiresAt,
            lastActivityAt: $lastActivityAt,
            roleActivatedAt: $roleActivatedAt,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            revokedAt: $revokedAt,
        );
    }

    public function revoke(): void
    {
        $this->revokedAt = new \DateTimeImmutable;
    }

    public function updateLastActivity(): void
    {
        $this->lastActivityAt = new \DateTimeImmutable;
    }

    public function rotateRefreshToken(string $newRefreshTokenHash, string $newAccessTokenHash): void
    {
        $this->refreshTokenHash = $newRefreshTokenHash;
        $this->accessTokenHash = $newAccessTokenHash;
        $this->lastActivityAt = new \DateTimeImmutable;
    }

    public function switchRole(RoleId $newRoleId, ?BranchId $newBranchId, string $newAccessTokenHash): void
    {
        $this->activeRoleId = $newRoleId;
        $this->activeBranchId = $newBranchId;
        $this->accessTokenHash = $newAccessTokenHash;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null && $this->refreshExpiresAt > new \DateTimeImmutable;
    }

    public function isRefreshExpired(): bool
    {
        return $this->refreshExpiresAt <= new \DateTimeImmutable;
    }

    public function getAccessTokenHash(): string
    {
        return $this->accessTokenHash;
    }

    public function getRefreshTokenHash(): string
    {
        return $this->refreshTokenHash;
    }

    public function getActiveRoleId(): RoleId
    {
        return $this->activeRoleId;
    }

    public function getActiveBranchId(): ?BranchId
    {
        return $this->activeBranchId;
    }

    public function getLastActivityAt(): \DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }
}
