<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationCreated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationDeactivated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationUpdated;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PharmaControl\Shared\Event\DomainEvent;

final class Presentation
{
    private PresentationName $name;

    private Abbreviation $abbreviation;

    private ?string $description;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly PresentationId $id,
        PresentationName $name,
        Abbreviation $abbreviation,
        ?string $description,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->name = $name;
        $this->abbreviation = $abbreviation;
        $this->description = $description;
        $this->isActive = $isActive;
    }

    public static function create(
        PresentationId $id,
        PresentationName $name,
        Abbreviation $abbreviation,
        ?string $description,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $presentation = new self($id, $name, $abbreviation, $description, true, $createdBy, $now, $now);
        $presentation->recordEvent(new PresentationCreated(
            id: $id,
            name: $name,
            abbreviation: $abbreviation,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $presentation;
    }

    public static function reconstitute(
        PresentationId $id,
        PresentationName $name,
        Abbreviation $abbreviation,
        ?string $description,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $abbreviation, $description, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(
        PresentationName $name,
        Abbreviation $abbreviation,
        ?string $description,
    ): void {
        $changes = [];

        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if (! $this->abbreviation->equals($abbreviation)) {
            $changes['abbreviation'] = ['old' => $this->abbreviation->value, 'new' => $abbreviation->value];
        }
        if ($this->description !== $description) {
            $changes['description'] = ['old' => $this->description, 'new' => $description];
        }

        $this->name = $name;
        $this->abbreviation = $abbreviation;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new PresentationUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('La presentación ya está inactiva.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new PresentationDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): PresentationId
    {
        return $this->id;
    }

    public function getName(): PresentationName
    {
        return $this->name;
    }

    public function getAbbreviation(): Abbreviation
    {
        return $this->abbreviation;
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
