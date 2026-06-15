<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\Event;

use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class StatusUpdated implements DomainEvent
{
    public function __construct(
        public readonly StatusId $id,
        public readonly array $changes,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
