<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/Event/CategoryDeactivated.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\Event;

use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class CategoryDeactivated implements DomainEvent
{
    public function __construct(
        public readonly CategoryId         $id,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
