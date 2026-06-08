<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/RoleAssigned.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;
use PharmaControl\Shared\ValueObject\BranchId;

final readonly class RoleAssigned implements DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly RoleId $roleId,
        public readonly ?BranchId $branchId,
        public readonly UserId $assignedBy,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
