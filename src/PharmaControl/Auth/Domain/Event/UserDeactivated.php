<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Event/UserDeactivated.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class UserDeactivated implements DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly ?UserId $deactivatedBy,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
