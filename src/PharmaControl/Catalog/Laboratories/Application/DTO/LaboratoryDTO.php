<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/DTO/LaboratoryDTO.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\DTO;

use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;

final readonly class LaboratoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $countryCode,
        public readonly ?string $website,
        public readonly bool $isActive,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromDomain(Laboratory $lab): self
    {
        return new self(
            id: $lab->getId()->value,
            name: $lab->getName()->value,
            countryCode: $lab->getCountryCode()->value,
            website: $lab->getWebsite()?->value,
            isActive: $lab->isActive(),
            createdBy: $lab->getCreatedBy()?->value,
            createdAt: $lab->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $lab->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
