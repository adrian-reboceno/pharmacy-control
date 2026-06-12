<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class PresentationCreated implements DomainEvent
{
    public function __construct(
        public readonly PresentationId $id,
        public readonly PresentationName $name,
        public readonly Abbreviation $abbreviation,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
