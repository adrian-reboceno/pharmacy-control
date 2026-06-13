<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\Event;

use PharmaControl\Shared\Event\DomainEvent;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;

final readonly class SupplierUpdated implements DomainEvent
{
    public function __construct(
        public readonly SupplierId $id,
        public readonly array $changes,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
