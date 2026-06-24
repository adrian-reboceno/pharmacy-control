<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Event;

use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class LocationDeactivated implements DomainEvent
{
    public function __construct(
        public readonly LocationId $id,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
