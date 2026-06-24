<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\GetLocations;

final readonly class GetLocationsQuery
{
    public function __construct(
        public readonly ?int $level = null,
        public readonly ?string $parentId = null,
        public readonly ?bool $isActive = null,
    ) {}
}
