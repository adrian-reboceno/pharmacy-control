<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\DTO;

use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;

final readonly class PresentationDTO
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $abbreviation,
        public readonly ?string $description,
        public readonly bool    $isActive,
        public readonly ?string $createdBy,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public static function fromDomain(Presentation $p): self
    {
        return new self(
            id:           $p->getId()->value,
            name:         $p->getName()->value,
            abbreviation: $p->getAbbreviation()->value,
            description:  $p->getDescription(),
            isActive:     $p->isActive(),
            createdBy:    $p->getCreatedBy()?->value,
            createdAt:    $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt:    $p->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
