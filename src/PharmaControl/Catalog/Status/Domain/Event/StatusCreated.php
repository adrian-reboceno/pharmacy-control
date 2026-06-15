<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class StatusCreated implements DomainEvent
{
    public function __construct(
        public readonly StatusId $id,
        public readonly StatusName $name,
        public readonly StatusCode $code,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
