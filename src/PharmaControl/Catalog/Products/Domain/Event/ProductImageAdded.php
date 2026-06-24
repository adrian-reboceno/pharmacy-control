<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Event;

use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class ProductImageAdded implements DomainEvent
{
    public function __construct(
        public readonly ProductId          $id,
        public readonly string             $imageUrl,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
