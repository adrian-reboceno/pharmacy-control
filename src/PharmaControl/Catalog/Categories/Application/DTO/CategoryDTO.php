<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/DTO/CategoryDTO.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\DTO;

use PharmaControl\Catalog\Categories\Domain\Model\Category;

final class CategoryDTO
{
    /** @var CategoryDTO[] */
    private array $children = [];

    public function __construct(
        public readonly string $id,
        public readonly ?string $parentId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly bool $isRoot,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public function addChild(self $child): void
    {
        $this->children[] = $child;
    }

    /** @return CategoryDTO[] */
    public function getChildren(): array
    {
        return $this->children;
    }

    public static function fromDomain(Category $c): self
    {
        return new self(
            id: $c->getId()->value,
            parentId: $c->getParentId()?->value,
            name: $c->getName()->value,
            slug: $c->getSlug()->value,
            description: $c->getDescription()?->value,
            isActive: $c->isActive(),
            isRoot: $c->isRoot(),
            createdBy: $c->getCreatedBy()?->value,
            createdAt: $c->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $c->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
