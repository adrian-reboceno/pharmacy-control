<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientCreated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientDeactivated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientUpdated;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PharmaControl\Shared\Event\DomainEvent;

final class ActiveIngredient
{
    private IngredientName $name;

    private DciCode $dciCode;

    private ?CasNumber $casNumber;

    private ?string $description;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly IngredientId $id,
        IngredientName $name,
        DciCode $dciCode,
        ?CasNumber $casNumber,
        ?string $description,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->name = $name;
        $this->dciCode = $dciCode;
        $this->casNumber = $casNumber;
        $this->description = $description;
        $this->isActive = $isActive;
    }

    public static function create(
        IngredientId $id,
        IngredientName $name,
        DciCode $dciCode,
        ?CasNumber $casNumber,
        ?string $description,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $ingredient = new self($id, $name, $dciCode, $casNumber, $description, true, $createdBy, $now, $now);
        $ingredient->recordEvent(new IngredientCreated(
            id: $id,
            name: $name,
            dciCode: $dciCode,
            casNumber: $casNumber,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $ingredient;
    }

    public static function reconstitute(
        IngredientId $id,
        IngredientName $name,
        DciCode $dciCode,
        ?CasNumber $casNumber,
        ?string $description,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $dciCode, $casNumber, $description, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(
        IngredientName $name,
        DciCode $dciCode,
        ?CasNumber $casNumber,
        ?string $description,
    ): void {
        $changes = [];

        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if (! $this->dciCode->equals($dciCode)) {
            $changes['dci_code'] = ['old' => $this->dciCode->value, 'new' => $dciCode->value];
        }
        if ($this->casNumber?->value !== $casNumber?->value) {
            $changes['cas_number'] = ['old' => $this->casNumber?->value, 'new' => $casNumber?->value];
        }
        if ($this->description !== $description) {
            $changes['description'] = ['old' => $this->description, 'new' => $description];
        }

        $this->name = $name;
        $this->dciCode = $dciCode;
        $this->casNumber = $casNumber;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new IngredientUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('El ingrediente activo ya está inactivo.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new IngredientDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): IngredientId
    {
        return $this->id;
    }

    public function getName(): IngredientName
    {
        return $this->name;
    }

    public function getDciCode(): DciCode
    {
        return $this->dciCode;
    }

    public function getCasNumber(): ?CasNumber
    {
        return $this->casNumber;
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
