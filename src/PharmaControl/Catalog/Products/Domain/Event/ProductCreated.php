<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductType;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class ProductCreated implements DomainEvent
{
    public function __construct(
        public readonly ProductId          $id,
        public readonly ProductType        $type,
        public readonly ProductName        $name,
        public readonly ?UserId            $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
