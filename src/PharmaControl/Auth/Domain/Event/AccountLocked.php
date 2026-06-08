<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/AccountLocked.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class AccountLocked implements DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly \DateTimeImmutable $lockedUntil,
        public readonly IpAddress $ipAddress,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
