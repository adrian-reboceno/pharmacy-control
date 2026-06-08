<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/TwoFactorFailed.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class TwoFactorFailed implements DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly IpAddress $ipAddress,
        public readonly int $attemptNumber,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
