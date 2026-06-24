<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class LocationCreated implements DomainEvent
{
    public function __construct(
        public readonly LocationId $id,
        public readonly LocationName $name,
        public readonly LocationLevel $level,
        public readonly ?LocationId $parentId,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
