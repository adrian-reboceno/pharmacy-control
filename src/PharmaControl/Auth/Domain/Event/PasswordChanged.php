<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/PasswordChanged.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class PasswordChanged implements DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly UserId $changedBy,
        public readonly bool $isForced,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
