<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

final readonly class ProductSpecs
{
    public function __construct(
        public readonly string  $unitId,
        public readonly string  $presentationId,
        public readonly string  $routeId,
        public readonly int     $unitsPerBox,
        public readonly int     $unitsPerBlister,
        public readonly ?string $locationId,
    ) {
        if ($unitsPerBox < 1) {
            throw new \InvalidArgumentException('Las unidades por caja deben ser >= 1.');
        }
        if ($unitsPerBlister < 1) {
            throw new \InvalidArgumentException('Las unidades por blister deben ser >= 1.');
        }
    }
}
