<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/Event/ClassificationUpdated.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\Event;

use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class ClassificationUpdated implements DomainEvent
{
    public function __construct(
        public readonly ClassificationId $id,
        public readonly LgsGroup $lgsGroup,
        public readonly array $changes,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
