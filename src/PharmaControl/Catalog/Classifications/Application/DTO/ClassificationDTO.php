<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Application/DTO/ClassificationDTO.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Application\DTO;

use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;

final readonly class ClassificationDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $lgsGroup,
        public readonly string $lgsGroupLabel,
        public readonly string $name,
        public readonly string $prescriptionType,
        public readonly string $prescriptionTypeLabel,
        public readonly ?int $validityDays,
        public readonly ?string $validityNote,
        public readonly bool $isControlled,
        public readonly bool $isActive,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromDomain(MedicationClassification $c): self
    {
        return new self(
            id: $c->getId()->value,
            lgsGroup: $c->getLgsGroup()->value,
            lgsGroupLabel: $c->getLgsGroup()->label(),
            name: $c->getName()->value,
            prescriptionType: $c->getPrescriptionType()->value,
            prescriptionTypeLabel: $c->getPrescriptionType()->label(),
            validityDays: $c->getValidityDays(),
            validityNote: $c->getValidityNote(),
            isControlled: $c->isControlled(),
            isActive: $c->isActive(),
            createdBy: $c->getCreatedBy()?->value,
            createdAt: $c->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $c->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
