<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/RoleRevoked.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class RoleRevoked implements DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly RoleId $roleId,
        public readonly UserId $revokedBy,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
