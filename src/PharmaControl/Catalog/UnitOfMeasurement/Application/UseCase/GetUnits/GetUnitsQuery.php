<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\GetUnits;

final readonly class GetUnitsQuery
{
    public function __construct(
        public readonly ?string $type     = null,
        public readonly ?string $search   = null,
        public readonly ?bool   $isActive = null,
        public readonly int     $perPage  = 20,
        public readonly int     $page     = 1,
    ) {}
}
