<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/Event/ClassificationCreated.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\Event;

use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class ClassificationCreated implements DomainEvent
{
    public function __construct(
        public readonly ClassificationId $id,
        public readonly LgsGroup $lgsGroup,
        public readonly ClassificationName $name,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
