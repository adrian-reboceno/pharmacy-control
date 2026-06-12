<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Application\DTO;

use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;

final readonly class UnitDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $symbol,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly bool $isActive,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromDomain(UnitOfMeasurement $unit): self
    {
        return new self(
            id: $unit->getId()->value,
            name: $unit->getName()->value,
            symbol: $unit->getSymbol()->value,
            type: $unit->getType()->value,
            typeLabel: $unit->getType()->label(),
            isActive: $unit->isActive(),
            createdBy: $unit->getCreatedBy()?->value,
            createdAt: $unit->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $unit->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
