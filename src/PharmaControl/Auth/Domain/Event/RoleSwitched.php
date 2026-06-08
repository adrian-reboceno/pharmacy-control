<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/RoleSwitched.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;
use PharmaControl\Shared\ValueObject\BranchId;

final readonly class RoleSwitched implements DomainEvent
{
    public function __construct(
        public readonly SessionId $sessionId,
        public readonly UserId $userId,
        public readonly RoleId $fromRoleId,
        public readonly RoleId $toRoleId,
        public readonly ?BranchId $branchId,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
