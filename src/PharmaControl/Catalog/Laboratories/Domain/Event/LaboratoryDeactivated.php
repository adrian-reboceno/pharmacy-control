<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/Event/LaboratoryDeactivated.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\Event;

use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class LaboratoryDeactivated implements DomainEvent
{
    public function __construct(
        public readonly LaboratoryId $id,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
