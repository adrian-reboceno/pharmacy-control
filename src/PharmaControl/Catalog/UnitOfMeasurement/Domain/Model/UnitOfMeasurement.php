<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitCreated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitDeactivated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitUpdated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;
use PharmaControl\Shared\Event\DomainEvent;

final class UnitOfMeasurement
{
    private UnitName $name;

    private UnitSymbol $symbol;

    private UnitType $type;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly UnitId $id,
        UnitName $name,
        UnitSymbol $symbol,
        UnitType $type,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->name = $name;
        $this->symbol = $symbol;
        $this->type = $type;
        $this->isActive = $isActive;
    }

    public static function create(
        UnitId $id,
        UnitName $name,
        UnitSymbol $symbol,
        UnitType $type,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $unit = new self($id, $name, $symbol, $type, true, $createdBy, $now, $now);
        $unit->recordEvent(new UnitCreated(
            id: $id,
            name: $name,
            symbol: $symbol,
            type: $type,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $unit;
    }

    public static function reconstitute(
        UnitId $id,
        UnitName $name,
        UnitSymbol $symbol,
        UnitType $type,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $symbol, $type, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(UnitName $name, UnitSymbol $symbol, UnitType $type): void
    {
        $changes = [];

        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if (! $this->symbol->equals($symbol)) {
            $changes['symbol'] = ['old' => $this->symbol->value, 'new' => $symbol->value];
        }
        if ($this->type !== $type) {
            $changes['type'] = ['old' => $this->type->value, 'new' => $type->value];
        }

        $this->name = $name;
        $this->symbol = $symbol;
        $this->type = $type;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new UnitUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('La unidad de medida ya está inactiva.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new UnitDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): UnitId
    {
        return $this->id;
    }

    public function getName(): UnitName
    {
        return $this->name;
    }

    public function getSymbol(): UnitSymbol
    {
        return $this->symbol;
    }

    public function getType(): UnitType
    {
        return $this->type;
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
