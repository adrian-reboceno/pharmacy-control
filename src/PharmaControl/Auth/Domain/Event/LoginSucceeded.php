<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/LoginSucceeded.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class LoginSucceeded implements DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly ClientType $clientType,
        public readonly IpAddress $ipAddress,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
