<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\DTO;

use PharmaControl\Catalog\Location\Domain\Model\Location;

final class LocationDTO
{
    /** @var list<LocationDTO> */
    private array $children = [];

    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $level,
        public readonly string $levelLabel,
        public readonly ?string $parentId,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly bool $isLeaf,
        public readonly string $fullPath,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public function addChild(self $child): void
    {
        $this->children[] = $child;
    }

    /** @return list<LocationDTO> */
    public function getChildren(): array
    {
        return $this->children;
    }

    public static function fromDomain(Location $l, string $fullPath): self
    {
        return new self(
            id: $l->getId()->value,
            name: $l->getName()->value,
            level: $l->getLevel()->value,
            levelLabel: $l->getLevel()->label(),
            parentId: $l->getParentId()?->value,
            description: $l->getDescription(),
            isActive: $l->isActive(),
            isLeaf: $l->isLeaf(),
            fullPath: $fullPath,
            createdBy: $l->getCreatedBy()?->value,
            createdAt: $l->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $l->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
