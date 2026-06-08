<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/LoginFailed.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class LoginFailed implements DomainEvent
{
    public function __construct(
        public readonly Email $email,
        public readonly IpAddress $ipAddress,
        public readonly int $attemptNumber,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
