<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\Event;

use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class PresentationUpdated implements DomainEvent
{
    public function __construct(
        public readonly PresentationId     $id,
        public readonly array              $changes,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
