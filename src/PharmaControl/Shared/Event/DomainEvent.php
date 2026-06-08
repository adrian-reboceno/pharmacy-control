<?php

// ── ARCHIVO: src/PharmaControl/Shared/Event/DomainEvent.php ──
declare(strict_types=1);

namespace PharmaControl\Shared\Event;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
