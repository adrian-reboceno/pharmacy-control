<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Model/AuditEntry.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Model;

use Illuminate\Support\Str;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\ValueObject\BranchId;
use PharmaControl\Shared\ValueObject\Uuid;

final readonly class AuditEntry
{
    public function __construct(
        public readonly string $id,
        public readonly UserId $userId,
        public readonly string $userEmail,
        public readonly string $userRole,
        public readonly ?BranchId $branchId,
        public readonly string $module,
        public readonly string $action,
        public readonly ?string $entityType,
        public readonly ?string $entityId,
        public readonly ?array $oldValues,
        public readonly ?array $newValues,
        public readonly ?array $metadata,
        public readonly IpAddress $ipAddress,
        public readonly ?string $userAgent,
        public readonly string $status,
        public readonly \DateTimeImmutable $timestamp,
    ) {}

    public static function createLoginSuccess(
        UserId $userId,
        string $userEmail,
        string $userRole,
        IpAddress $ip,
        ?string $userAgent,
    ): self {
        return new self(
            // id:         Uuid::generate() ?? bin2hex(random_bytes(16)),
            id: Str::uuid()->toString(),
            userId: $userId,
            userEmail: $userEmail,
            userRole: $userRole,
            branchId: null,
            module: 'auth',
            action: 'LOGIN_SUCCESS',
            entityType: null,
            entityId: null,
            oldValues: null,
            newValues: null,
            metadata: null,
            ipAddress: $ip,
            userAgent: $userAgent,
            status: 'SUCCESS',
            timestamp: new \DateTimeImmutable,
        );
    }

    public static function create(
        UserId $userId,
        string $userEmail,
        string $userRole,
        ?BranchId $branchId,
        string $module,
        string $action,
        ?string $entityType,
        ?string $entityId,
        ?array $oldValues,
        ?array $newValues,
        ?array $metadata,
        IpAddress $ipAddress,
        ?string $userAgent,
        string $status,
    ): self {
        return new self(
            id: bin2hex(random_bytes(16)),
            userId: $userId,
            userEmail: $userEmail,
            userRole: $userRole,
            branchId: $branchId,
            module: $module,
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            status: $status,
            timestamp: new \DateTimeImmutable,
        );
    }
}
