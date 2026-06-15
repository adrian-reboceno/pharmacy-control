<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\DTO;

use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;

final readonly class IngredientDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $dciCode,
        public readonly ?string $casNumber,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromDomain(ActiveIngredient $i): self
    {
        return new self(
            id: $i->getId()->value,
            name: $i->getName()->value,
            dciCode: $i->getDciCode()->value,
            casNumber: $i->getCasNumber()?->value,
            description: $i->getDescription(),
            isActive: $i->isActive(),
            createdBy: $i->getCreatedBy()?->value,
            createdAt: $i->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $i->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
