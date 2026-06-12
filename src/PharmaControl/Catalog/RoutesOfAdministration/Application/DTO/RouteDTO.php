<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Application\DTO;

use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;

final readonly class RouteDTO
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $code,
        public readonly ?string $description,
        public readonly bool    $isActive,
        public readonly ?string $createdBy,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public static function fromDomain(RouteOfAdministration $r): self
    {
        return new self(
            id:          $r->getId()->value,
            name:        $r->getName()->value,
            code:        $r->getCode()->value,
            description: $r->getDescription(),
            isActive:    $r->isActive(),
            createdBy:   $r->getCreatedBy()?->value,
            createdAt:   $r->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt:   $r->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
