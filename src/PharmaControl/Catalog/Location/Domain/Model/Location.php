<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Location\Domain\Event\LocationCreated;
use PharmaControl\Catalog\Location\Domain\Event\LocationDeactivated;
use PharmaControl\Catalog\Location\Domain\Event\LocationUpdated;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;
use PharmaControl\Shared\Event\DomainEvent;

final class Location
{
    private LocationName $name;

    private ?string $description;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly LocationId $id,
        public readonly LocationLevel $level,
        public readonly ?LocationId $parentId,
        LocationName $name,
        ?string $description,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->isActive = $isActive;
    }

    public static function create(
        LocationId $id,
        LocationLevel $level,
        ?LocationId $parentId,
        LocationName $name,
        ?string $description,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $location = new self($id, $level, $parentId, $name, $description, true, $createdBy, $now, $now);
        $location->recordEvent(new LocationCreated(
            id: $id,
            name: $name,
            level: $level,
            parentId: $parentId,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $location;
    }

    public static function reconstitute(
        LocationId $id,
        LocationLevel $level,
        ?LocationId $parentId,
        LocationName $name,
        ?string $description,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $level, $parentId, $name, $description, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(LocationName $name, ?string $description): void
    {
        $changes = [];

        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if ($this->description !== $description) {
            $changes['description'] = ['old' => $this->description, 'new' => $description];
        }

        $this->name = $name;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new LocationUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('La ubicación ya está inactiva.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new LocationDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function getId(): LocationId
    {
        return $this->id;
    }

    public function getLevel(): LocationLevel
    {
        return $this->level;
    }

    public function getParentId(): ?LocationId
    {
        return $this->parentId;
    }

    public function getName(): LocationName
    {
        return $this->name;
    }

    public function getDescription(): ?string
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

    public function isLeaf(): bool
    {
        return $this->level->isLeaf();
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
