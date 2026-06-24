<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\DTO;

use PharmaControl\Catalog\Products\Domain\ValueObject\ProductSpecs;

final readonly class ProductSpecsDTO
{
    public function __construct(
        public readonly string $unitId,
        public readonly string $presentationId,
        public readonly string $routeId,
        public readonly int $unitsPerBox,
        public readonly int $unitsPerBlister,
        public readonly ?string $locationId,
    ) {}

    public static function fromDomain(ProductSpecs $specs): self
    {
        return new self(
            unitId: $specs->unitId,
            presentationId: $specs->presentationId,
            routeId: $specs->routeId,
            unitsPerBox: $specs->unitsPerBox,
            unitsPerBlister: $specs->unitsPerBlister,
            locationId: $specs->locationId,
        );
    }
}
