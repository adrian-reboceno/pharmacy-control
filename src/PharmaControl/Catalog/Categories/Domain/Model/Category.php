<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/Model/Category.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryCreated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryDeactivated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryUpdated;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryDescription;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PharmaControl\Shared\Event\DomainEvent;

final class Category
{
    private ?CategoryId $parentId;

    private CategoryName $name;

    private CategorySlug $slug;

    private ?CategoryDescription $description;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly CategoryId $id,
        ?CategoryId $parentId,
        CategoryName $name,
        CategorySlug $slug,
        ?CategoryDescription $description,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->parentId = $parentId;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->isActive = $isActive;
    }

    public static function create(
        CategoryId $id,
        ?CategoryId $parentId,
        CategoryName $name,
        CategorySlug $slug,
        ?CategoryDescription $description,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $category = new self($id, $parentId, $name, $slug, $description, true, $createdBy, $now, $now);
        $category->recordEvent(new CategoryCreated(
            id: $id,
            parentId: $parentId,
            name: $name,
            slug: $slug,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $category;
    }

    public static function reconstitute(
        CategoryId $id,
        ?CategoryId $parentId,
        CategoryName $name,
        CategorySlug $slug,
        ?CategoryDescription $description,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $parentId, $name, $slug, $description, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(
        ?CategoryId $parentId,
        CategoryName $name,
        CategorySlug $slug,
        ?CategoryDescription $description,
    ): void {
        $changes = [];

        if ($this->parentId?->value !== $parentId?->value) {
            $changes['parentId'] = ['old' => $this->parentId?->value, 'new' => $parentId?->value];
        }
        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if (! $this->slug->equals($slug)) {
            $changes['slug'] = ['old' => $this->slug->value, 'new' => $slug->value];
        }
        if ($this->description?->value !== $description?->value) {
            $changes['description'] = ['old' => $this->description?->value, 'new' => $description?->value];
        }

        $this->parentId = $parentId;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new CategoryUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('La categoría ya está inactiva.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new CategoryDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): CategoryId
    {
        return $this->id;
    }

    public function getParentId(): ?CategoryId
    {
        return $this->parentId;
    }

    public function getName(): CategoryName
    {
        return $this->name;
    }

    public function getSlug(): CategorySlug
    {
        return $this->slug;
    }

    public function getDescription(): ?CategoryDescription
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedBy(): ?UserId
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
