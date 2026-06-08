<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/SessionRevoked.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class SessionRevoked implements DomainEvent
{
    public function __construct(
        public readonly SessionId $sessionId,
        public readonly UserId $userId,
        public readonly ?UserId $revokedBy,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
