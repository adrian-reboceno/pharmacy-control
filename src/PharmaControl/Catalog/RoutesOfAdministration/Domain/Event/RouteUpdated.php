<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\Event;

use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class RouteUpdated implements DomainEvent
{
    public function __construct(
        public readonly RouteId $id,
        public readonly array $changes,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
