<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/Event/CategoryCreated.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class CategoryCreated implements DomainEvent
{
    public function __construct(
        public readonly CategoryId         $id,
        public readonly ?CategoryId        $parentId,
        public readonly CategoryName       $name,
        public readonly CategorySlug       $slug,
        public readonly ?UserId            $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
